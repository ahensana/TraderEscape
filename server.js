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

// Serve uploaded files
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

    // Notify others about new user
    socket.broadcast.emit("user-joined", {
      message: `${user.name} joined the chat`,
      user: user,
    });

    console.log(
      `${user.name} (${user.id}) joined the chat from ${
        user.deviceInfo.deviceType || "Unknown"
      } (${user.deviceInfo.browser || "Unknown"})`
    );
  });

  // Handle new messages
  socket.on("message", (messageData) => {
    const user = connectedUsers.get(socket.id);
    if (!user) return;

    const message = {
      id: messageData.messageId || Date.now(), // Use client messageId if provided
      text: messageData.text,
      sender: user.name,
      senderId: user.id, // This will now be the client's user ID
      timestamp: new Date(),
      color: user.color,
      file: messageData.file || null, // Support file attachments
    };

    // Store message in history
    messageHistory.push(message);

    // Keep only last 100 messages
    if (messageHistory.length > 100) {
      messageHistory.shift();
    }

    // Broadcast message to all clients
    io.emit("new-message", message);

    console.log(
      `Message from ${user.name}: ${messageData.text}${
        messageData.file ? " (with file)" : ""
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
      file: messageData.file || null,
    };

    messageHistory.push(message);
    if (messageHistory.length > 100) {
      messageHistory.shift();
    }

    io.emit("new-message", message);
    io.emit("new_message", {
      id: message.id,
      username: user.name,
      content: message.text,
      file: message.file,
      timestamp: message.timestamp,
      type: message.file ? "file" : "user",
    });

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
      // Remove user entry
      connectedUsers.delete(socket.id);

      // Update user list for remaining clients
      io.emit("user-list", Array.from(connectedUsers.values()));

      // Notify others about user leaving
      socket.broadcast.emit("user-left", {
        message: `${user.name} left the chat`,
        user: user,
      });

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
