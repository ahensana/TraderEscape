const express = require("express");
const http = require("http");
const socketIo = require("socket.io");
const cors = require("cors");
const path = require("path");

const app = express();
const server = http.createServer(app);
const io = socketIo(server, {
  cors: {
    origin: "*",
    methods: ["GET", "POST"],
  },
});

// Middleware
app.use(cors());
app.use(express.json());
app.use(express.static("."));

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

// Socket.IO connection handling
io.on("connection", (socket) => {
  console.log("User connected:", socket.id);

  // Handle user joining
  socket.on("user-join", (userData) => {
    // Check if user already exists to prevent duplicate joins
    if (connectedUsers.has(socket.id)) {
      console.log(
        `User already exists for socket ${socket.id}, ignoring duplicate join`
      );
      return;
    }

    const userId = userData.userId || socket.id; // Use client's user ID if provided
    const user = {
      id: userId,
      name: userData.name || `User_${socket.id.substring(0, 6)}`,
      color: userData.color || "#3b82f6",
      joinTime: new Date(),
      socketId: socket.id, // Store socket ID for reference
    };

    // Store user with socket.id as key, but use userId in messages
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

    console.log(`${user.name} joined the chat`);
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
    };

    // Store message in history
    messageHistory.push(message);

    // Keep only last 100 messages
    if (messageHistory.length > 100) {
      messageHistory.shift();
    }

    // Broadcast message to all clients
    io.emit("new-message", message);

    console.log(`Message from ${user.name}: ${messageData.text}`);
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
server.listen(PORT, () => {
  console.log(`Chat server running on port ${PORT}`);
  console.log(`Access the chat at: http://localhost:${PORT}/chat.php`);
});

// Graceful shutdown
process.on("SIGTERM", () => {
  console.log("Shutting down server...");
  server.close(() => {
    console.log("Server closed");
    process.exit(0);
  });
});
