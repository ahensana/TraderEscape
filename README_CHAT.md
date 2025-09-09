# TraderEscape Community Chat

A real-time community chat system built with Socket.IO, Node.js, and PHP.

## Features

- ✅ Real-time messaging
- ✅ Online user list
- ✅ Typing indicators
- ✅ Message history
- ✅ Responsive design
- ✅ User avatars with colors
- ✅ Connection status indicator
- ✅ Mobile-friendly interface

## Setup Instructions

### 1. Install Node.js Dependencies

```bash
npm install
```

### 2. Start the Chat Server

```bash
# Development mode (with auto-restart)
npm run dev

# Or production mode
npm start
```

The server will start on `http://localhost:3000`

### 3. Access the Chat

- **Homepage**: `http://localhost/TraderEscape/` (click the chat icon)
- **Direct Chat**: `http://localhost/TraderEscape/chat.php`
- **Node.js Server**: `http://localhost:3000/` (for Socket.IO only)

## How It Works

### Frontend (chat.php)

- Modern chat interface with sidebar for online users
- Real-time message display
- Typing indicators
- Connection status
- Mobile responsive design

### Backend (server.js)

- Express.js server with Socket.IO
- Real-time bidirectional communication
- User management
- Message history (last 100 messages)
- Automatic reconnection handling

### Chat Button Integration

- Click the blue chat icon on the homepage
- Redirects to the community chat page
- Seamless integration with existing site

## Technical Details

### Socket.IO Events

**Client to Server:**

- `user-join`: User joins the chat
- `message`: Send a new message
- `typing`: Typing indicator

**Server to Client:**

- `new-message`: New message received
- `message-history`: Previous messages
- `user-list`: Updated online users
- `user-joined`: User joined notification
- `user-left`: User left notification
- `user-typing`: Typing indicator

### File Structure

```
├── chat.php              # Chat interface
├── server.js             # Node.js server
├── package.json          # Dependencies
├── index.php             # Homepage (with chat button)
└── README_CHAT.md        # This file
```

## Customization

### Change Server Port

Edit `server.js`:

```javascript
const PORT = process.env.PORT || 3000; // Change 3000 to your preferred port
```

### Update Socket.IO Connection

Edit `chat.php`:

```javascript
this.socket = io("http://localhost:3000"); // Change URL if needed
```

### Styling

All styles are in `chat.php` within the `<style>` tags. You can customize:

- Colors and themes
- Layout and spacing
- Mobile responsiveness
- Animation effects

## Production Deployment

### 1. Environment Variables

```bash
export PORT=3000
export NODE_ENV=production
```

### 2. Process Management

Use PM2 for production:

```bash
npm install -g pm2
pm2 start server.js --name "traderescape-chat"
```

### 3. Reverse Proxy

Configure Nginx or Apache to proxy requests to the Node.js server.

## Troubleshooting

### Chat Not Connecting

1. Ensure Node.js server is running
2. Check browser console for errors
3. Verify port 3000 is not blocked
4. Check firewall settings

### Messages Not Sending

1. Check connection status indicator
2. Verify Socket.IO connection
3. Check server logs for errors

### Mobile Issues

1. Ensure responsive CSS is working
2. Check touch events
3. Verify mobile navigation doesn't overlap

## Security Considerations

For production use, consider:

- User authentication
- Message moderation
- Rate limiting
- Input sanitization
- HTTPS/SSL certificates
- CORS configuration

## Support

For issues or questions:

1. Check the browser console for errors
2. Check server logs
3. Verify all dependencies are installed
4. Ensure ports are not blocked
