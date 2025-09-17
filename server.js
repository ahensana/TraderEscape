const express = require("express");
const http = require("http");
const socketIo = require("socket.io");
const cors = require("cors");
const path = require("path");
const multer = require("multer");
const fs = require("fs");

const app = express();
const server = http.createServer(app);
const io = socketIo(server, {
  cors: {
    origin: [
      "http://localhost:3000",
      "http://localhost:5173",
      "http://localhost",
      "http://192.168.29.127",
    ],
    methods: ["GET", "POST"],
    credentials: true,
  },
});

// Middleware
app.use(cors());
app.use(express.json());
app.use(express.static("."));

// File upload configuration
const storage = multer.diskStorage({
  destination: (req, file, cb) => {
    const uploadDir = "uploads/";
    if (!fs.existsSync(uploadDir)) {
      fs.mkdirSync(uploadDir, { recursive: true });
    }
    cb(null, uploadDir);
  },
  filename: (req, file, cb) => {
    const uniqueSuffix = Date.now() + "-" + Math.round(Math.random() * 1e9);
    cb(
      null,
      file.fieldname + "-" + uniqueSuffix + path.extname(file.originalname)
    );
  },
});

const upload = multer({
  storage: storage,
  limits: {
    fileSize: 10 * 1024 * 1024, // 10MB limit
  },
  fileFilter: (req, file, cb) => {
    // Allow images, documents, and archives
    const allowedTypes = /jpeg|jpg|png|gif|pdf|doc|docx|txt|zip|rar/;
    const extname = allowedTypes.test(
      path.extname(file.originalname).toLowerCase()
    );
    const mimetype = allowedTypes.test(file.mimetype);

    if (mimetype && extname) {
      return cb(null, true);
    } else {
      cb(
        new Error(
          "Invalid file type. Only images, documents, and archives are allowed."
        )
      );
    }
  },
});

// Store connected users
const connectedUsers = new Map();
const messageHistory = [];
// Track recent disconnections to detect page refreshes
const recentDisconnections = new Map(); // baseId -> timestamp

// Clean up old disconnection records every 30 seconds
setInterval(() => {
  const now = Date.now();
  const maxAge = 30000; // 30 seconds

  for (const [baseId, timestamp] of recentDisconnections.entries()) {
    if (now - timestamp > maxAge) {
      recentDisconnections.delete(baseId);
    }
  }
}, 30000);

// Routes - Note: PHP files should be served by XAMPP, not Node.js
app.get("/", (req, res) => {
  res.json({
    message: "TraderEscape Chat Server",
    status: "running",
    note: "Access chat at http://localhost/TraderEscape/chat.php",
  });
});

// File upload endpoint
app.post("/api/upload", upload.single("file"), (req, res) => {
  if (!req.file) {
    return res.status(400).json({ error: "No file uploaded" });
  }

  const fileData = {
    originalName: req.file.originalname,
    filename: req.file.filename,
    mimetype: req.file.mimetype,
    size: req.file.size,
    url: `/uploads/${req.file.filename}`,
  };

  res.json(fileData);
});

// Serve uploaded files with proper headers for browser viewing
app.get("/uploads/:filename", (req, res) => {
  const filename = req.params.filename;
  const filePath = path.join(__dirname, "uploads", filename);

  // Check if file exists
  if (!fs.existsSync(filePath)) {
    return res.status(404).send("File not found");
  }

  // Get file extension
  const ext = path.extname(filename).toLowerCase();

  // Set appropriate headers based on file type
  if (ext === ".pdf") {
    res.setHeader("Content-Type", "application/pdf");
    res.setHeader("Content-Disposition", 'inline; filename="' + filename + '"');
  } else if (ext === ".doc" || ext === ".docx") {
    res.setHeader(
      "Content-Type",
      "application/vnd.openxmlformats-officedocument.wordprocessingml.document"
    );
    res.setHeader("Content-Disposition", 'inline; filename="' + filename + '"');
  } else if (ext === ".xls" || ext === ".xlsx") {
    res.setHeader(
      "Content-Type",
      "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
    );
    res.setHeader("Content-Disposition", 'inline; filename="' + filename + '"');
  } else if (ext === ".txt") {
    res.setHeader("Content-Type", "text/plain");
    res.setHeader("Content-Disposition", 'inline; filename="' + filename + '"');
  } else {
    // For other files, use default behavior
    res.setHeader(
      "Content-Disposition",
      'attachment; filename="' + filename + '"'
    );
  }

  // Send the file
  res.sendFile(filePath);
});

// Fallback for other uploads (images, etc.)
app.use("/uploads", express.static("uploads"));

// Socket.IO connection handling
io.on("connection", (socket) => {
  console.log("User connected:", socket.id);

  // Add timeout to detect if user doesn't join within 5 seconds
  const joinTimeout = setTimeout(() => {
    if (!connectedUsers.has(socket.id)) {
      console.log(
        `Warning: User ${socket.id} connected but never joined the chat`
      );
    }
  }, 5000);

  // Handle user joining
  socket.on("user-join", (userData) => {
    // Clear the join timeout since user is joining
    clearTimeout(joinTimeout);

    // Check if user already exists to prevent duplicate joins
    if (connectedUsers.has(socket.id)) {
      console.log(
        `User already exists for socket ${socket.id}, ignoring duplicate join`
      );
      return;
    }

    // Create unique user ID combining userId and socket.id for better identification
    const baseUserId = userData.userId || `guest_${socket.id.substring(0, 6)}`;
    const uniqueUserId = `${baseUserId}_${socket.id.substring(0, 6)}`;

    const user = {
      id: uniqueUserId,
      baseId: baseUserId, // Keep original user ID for reference
      name: userData.name || `User_${socket.id.substring(0, 6)}`,
      color: userData.color || "#3b82f6",
      joinTime: new Date(),
      socketId: socket.id, // Store socket ID for reference
      deviceInfo: userData.deviceInfo || "Unknown Device",
    };

    // Store user with socket.id as key
    connectedUsers.set(socket.id, user);

    // Send user list to all clients
    io.emit("user-list", Array.from(connectedUsers.values()));

    // Send message history to new user
    socket.emit("message-history", messageHistory.slice(-50)); // Last 50 messages

    // Check if this is a reconnection (same baseId already exists OR recently disconnected)
    const isReconnection =
      Array.from(connectedUsers.values()).some(
        (existingUser) =>
          existingUser.baseId === user.baseId &&
          existingUser.socketId !== socket.id
      ) || recentDisconnections.has(user.baseId);

    if (!isReconnection) {
      // Only notify about new user joining if it's not a reconnection
      socket.broadcast.emit("user-joined", {
        message: `${user.name} joined the chat`,
        user: user,
        userId: user.baseId, // Send the original user ID for matching
      });
      console.log(
        `${user.name} (${user.id}) joined the chat from ${
          user.deviceInfo.deviceType || "Unknown"
        } (${user.deviceInfo.browser || "Unknown"})`
      );
    } else {
      console.log(
        `${user.name} reconnected (page refresh) - not showing join message`
      );
      // Clean up the recent disconnection tracking since user reconnected
      recentDisconnections.delete(user.baseId);
    }
  });

  // Handle new messages
  socket.on("message", (messageData) => {
    const user = connectedUsers.get(socket.id);
    if (!user) return;

    console.log("=== RECEIVED MESSAGE DATA ===");
    console.log("Text:", messageData.text);
    console.log("ReplyTo:", messageData.replyTo);
    console.log("ReplyToId:", messageData.replyToId);
    console.log("Full messageData:", JSON.stringify(messageData, null, 2));
    console.log("=============================");

    const message = {
      id: messageData.messageId || Date.now(), // Use client messageId if provided
      text: messageData.text,
      sender: user.name,
      senderId: user.id, // This will now be the client's user ID
      timestamp: new Date(),
      color: user.color,
      files: messageData.files || null, // Support multiple file attachments
      replyTo: messageData.replyTo || null, // Include reply data
      replyToId: messageData.replyToId || null, // Include reply ID
    };

    // Store message in history
    messageHistory.push(message);

    // Keep only last 100 messages
    if (messageHistory.length > 100) {
      messageHistory.shift();
    }

    // Broadcast message to all clients
    io.emit("new-message", message);

    console.log("=== BROADCASTING TO ALL CLIENTS ===");
    console.log("Message ID:", message.id);
    console.log("Text:", message.text);
    console.log("ReplyTo:", message.replyTo);
    console.log("ReplyToId:", message.replyToId);
    console.log("Full message object:", JSON.stringify(message, null, 2));
    console.log("===================================");

    console.log(
      `Message from ${user.name}: ${messageData.text}${
        messageData.file ? " (with file)" : ""
      }${
        messageData.replyTo
          ? " (reply to: " + messageData.replyTo.sender + ")"
          : ""
      }`
    );
  });

  // Handle typing indicators
  socket.on("typing", (isTyping) => {
    const user = connectedUsers.get(socket.id);
    if (!user) return;

    socket.broadcast.emit("user-typing", {
      userId: socket.id,
      userName: user.name,
      isTyping: isTyping,
    });
  });

  // React Chat Events - Additional support for React frontend
  socket.on("user_join", ({ username }) => {
    // Check if user already exists to prevent duplicate joins
    if (connectedUsers.has(socket.id)) {
      console.log(
        `User already exists for socket ${socket.id}, ignoring duplicate join`
      );
      return;
    }

    const baseUserId = `user_${Date.now()}`;
    const uniqueUserId = `${baseUserId}_${socket.id.substring(0, 6)}`;

    const user = {
      id: uniqueUserId,
      baseId: baseUserId,
      name: username || `User_${socket.id.substring(0, 6)}`,
      color: "#3b82f6",
      joinTime: new Date(),
      socketId: socket.id,
      deviceInfo: {
        deviceType: "Unknown",
        browser: "Unknown",
      },
    };

    connectedUsers.set(socket.id, user);
    io.emit("user-list", Array.from(connectedUsers.values()));
    socket.emit("user_joined", { userId: socket.id, username: user.name });
    socket.emit("message-history", messageHistory.slice(-50));
    socket.broadcast.emit("user-joined", {
      message: `${user.name} joined the chat`,
      user: user,
      userId: user.baseId, // Send the original user ID for matching
    });

    console.log(`${user.name} (${user.id}) joined the chat via React`);
  });

  socket.on("send_message", (messageData) => {
    const user = connectedUsers.get(socket.id);
    if (!user) return;

    const message = {
      id: messageData.messageId || Date.now(),
      text: messageData.content,
      sender: user.name,
      senderId: user.id,
      timestamp: new Date(),
      color: user.color,
      files: messageData.files || null,
    };

    messageHistory.push(message);
    if (messageHistory.length > 100) {
      messageHistory.shift();
    }

    // Only emit in PHP format for PHP chat
    io.emit("new-message", message);

    console.log(
      `Message from ${user.name}: ${messageData.content}${
        messageData.file ? " (with file)" : ""
      }`
    );
  });

  socket.on("typing_start", () => {
    const user = connectedUsers.get(socket.id);
    if (user) {
      io.emit("user_typing", { userId: socket.id, username: user.name });
    }
  });

  socket.on("typing_stop", () => {
    io.emit("user_stop_typing", { userId: socket.id });
  });

  // Handle clear chat request
  socket.on("clear-chat", () => {
    const user = connectedUsers.get(socket.id);
    if (!user) return;

    // Clear message history
    messageHistory.length = 0;

    // Notify all clients to clear their chat
    io.emit("chat-cleared", {
      message: `${user.name} cleared the chat`,
      clearedBy: user.name,
    });

    console.log(`${user.name} cleared the chat`);
  });

  // Handle user disconnect
  socket.on("disconnect", () => {
    const user = connectedUsers.get(socket.id);
    if (user) {
      // Track this disconnection for potential page refresh detection
      recentDisconnections.set(user.baseId, Date.now());

      // Remove user entry
      connectedUsers.delete(socket.id);

      // Update user list for remaining clients
      io.emit("user-list", Array.from(connectedUsers.values()));

      // Add a small delay before notifying about user leaving
      // This helps detect if it's a page refresh (user reconnects quickly)
      setTimeout(() => {
        // Check if user has reconnected (same baseId exists in connectedUsers)
        const hasReconnected = Array.from(connectedUsers.values()).some(
          (existingUser) => existingUser.baseId === user.baseId
        );

        if (!hasReconnected) {
          // Only notify if user hasn't reconnected (genuine disconnect)
          socket.broadcast.emit("user-left", {
            message: `${user.name} left the chat`,
            user: user,
            userId: user.baseId, // Send the original user ID for matching
          });
          console.log(`${user.name} left the chat`);
        } else {
          console.log(`${user.name} reconnected (page refresh detected)`);
        }

        // Clean up the recent disconnection tracking
        recentDisconnections.delete(user.baseId);
      }, 1000); // 1 second delay

      console.log(`${user.name} disconnected`);
    }
  });

  // Handle errors
  socket.on("error", (error) => {
    console.error("Socket error:", error);
  });
});

// Start server
const PORT = process.env.PORT || 3000;
server.listen(PORT, "0.0.0.0", () => {
  console.log(`Chat server running on port ${PORT}`);
  console.log(`Access the chat at: http://localhost:${PORT}/chat.php`);
  console.log(`Network access: http://[YOUR_IP]:${PORT}/chat.php`);
  console.log(
    `To find your IP, run: ipconfig (Windows) or ifconfig (Mac/Linux)`
  );
});

// Graceful shutdown
process.on("SIGTERM", () => {
  console.log("Shutting down server...");
  server.close(() => {
    console.log("Server closed");
    process.exit(0);
  });
});
