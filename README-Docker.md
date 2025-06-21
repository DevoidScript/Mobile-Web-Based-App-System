# Blood Donation App - Docker Setup

This document provides instructions for running the Blood Donation Application using Docker.

## 🐳 Prerequisites

Before you begin, ensure you have the following installed on your system:

- **Docker** (version 20.10 or higher)
- **Docker Compose** (version 2.0 or higher)

### Installing Docker

#### Windows
1. Download Docker Desktop from [https://www.docker.com/products/docker-desktop](https://www.docker.com/products/docker-desktop)
2. Install and follow the setup wizard
3. Start Docker Desktop

#### macOS
1. Download Docker Desktop from [https://www.docker.com/products/docker-desktop](https://www.docker.com/products/docker-desktop)
2. Install and follow the setup wizard
3. Start Docker Desktop

#### Linux (Ubuntu/Debian)
```bash
# Update package index
sudo apt-get update

# Install prerequisites
sudo apt-get install apt-transport-https ca-certificates curl gnupg lsb-release

# Add Docker's official GPG key
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg

# Add Docker repository
echo "deb [arch=amd64 signed-by=/usr/share/keyrings/docker-archive-keyring.gpg] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null

# Install Docker
sudo apt-get update
sudo apt-get install docker-ce docker-ce-cli containerd.io

# Install Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/download/v2.20.0/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# Add user to docker group (optional, to run without sudo)
sudo usermod -aG docker $USER
```

## 🚀 Quick Start

### Option 1: Using the Setup Script (Recommended)

1. **Make the script executable:**
   ```bash
   chmod +x docker-setup.sh
   ```

2. **Run the setup script:**
   ```bash
   ./docker-setup.sh
   ```

3. **Follow the interactive menu to build and run the application**

### Option 2: Manual Docker Commands

1. **Build the Docker image:**
   ```bash
   docker-compose build
   ```

2. **Start the application:**
   ```bash
   docker-compose up -d
   ```

3. **Access the application:**
   - Main application: http://localhost:8080
   - Mobile app: http://localhost:8080/mobile-app/

## 📱 Application Access

Once the Docker container is running, you can access the application at:

- **Main Entry Point:** http://localhost:8080
- **Mobile App:** http://localhost:8080/mobile-app/
- **Donor Form:** http://localhost:8080/mobile-app/templates/forms/donor-form-modal.php
- **Medical History Form:** http://localhost:8080/mobile-app/templates/forms/medical-history-modal.php

## 🛠️ Docker Commands

### Basic Commands

```bash
# Build the image
docker-compose build

# Start the application (in background)
docker-compose up -d

# Start the application (with logs)
docker-compose up

# Stop the application
docker-compose down

# View logs
docker-compose logs -f

# Restart the application
docker-compose restart

# Rebuild and start (if you made changes)
docker-compose up --build
```

### Development Commands

```bash
# Access the container shell
docker-compose exec blood-donation-app bash

# View container status
docker-compose ps

# View resource usage
docker stats

# Clean up unused resources
docker system prune
```

## 🔧 Configuration

### Environment Variables

The application uses Supabase as the backend. The database configuration is already set in `mobile-app/config/database.php`.

### Port Configuration

The application runs on port 8080 by default. To change this, modify the `docker-compose.yml` file:

```yaml
ports:
  - "YOUR_PORT:80"  # Change YOUR_PORT to your desired port
```

### Volume Mounts

The application code is mounted as a volume, so changes to your local files will be reflected in the container without rebuilding.

## 🐛 Troubleshooting

### Common Issues

1. **Port already in use:**
   ```bash
   # Check what's using port 8080
   lsof -i :8080
   
   # Or change the port in docker-compose.yml
   ```

2. **Permission denied:**
   ```bash
   # Fix file permissions
   sudo chown -R $USER:$USER .
   chmod +x docker-setup.sh
   ```

3. **Container won't start:**
   ```bash
   # Check logs
   docker-compose logs blood-donation-app
   
   # Rebuild the image
   docker-compose up --build
   ```

4. **Database connection issues:**
   - Verify your Supabase credentials in `mobile-app/config/database.php`
   - Check if your Supabase project is active

### Logs and Debugging

```bash
# View real-time logs
docker-compose logs -f blood-donation-app

# View specific service logs
docker-compose logs blood-donation-app

# Access container for debugging
docker-compose exec blood-donation-app bash
```

## 🧹 Cleanup

### Stop and Remove Containers
```bash
docker-compose down
```

### Remove All Docker Resources
```bash
docker-compose down -v --remove-orphans
docker system prune -a
```

### Complete Cleanup (Use with caution)
```bash
# This will remove all Docker images, containers, and volumes
docker system prune -a --volumes
```

## 📋 Project Structure in Docker

```
/var/www/html/
├── mobile-app/           # Main application directory
│   ├── config/          # Configuration files
│   ├── templates/       # PHP templates
│   ├── includes/        # PHP includes
│   ├── api/            # API endpoints
│   └── index.php       # Entry point
├── images/             # Static images
├── manifest.json       # PWA manifest
├── service-worker.js   # Service worker
└── offline.html        # Offline page
```

## 🔒 Security Notes

- The application runs as `www-data` user inside the container
- File permissions are set to 755 for directories and 644 for files
- The forms directory has 777 permissions for file uploads
- Apache is configured with proper security headers

## 📞 Support

If you encounter any issues:

1. Check the troubleshooting section above
2. View the application logs: `docker-compose logs -f`
3. Verify your Docker and Docker Compose versions
4. Ensure all prerequisites are installed correctly

## 🚀 Production Deployment

For production deployment, consider:

1. Using environment variables for sensitive data
2. Setting up proper SSL/TLS certificates
3. Configuring a reverse proxy (nginx)
4. Setting up proper backup strategies
5. Monitoring and logging solutions

---

**Happy coding! 🩸💉** 