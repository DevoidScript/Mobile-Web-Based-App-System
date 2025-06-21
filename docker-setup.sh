#!/bin/bash

# Blood Donation App Docker Setup Script
# This script helps you build and run the Blood Donation application in Docker

echo "🚀 Blood Donation App Docker Setup"
echo "=================================="

# Check if Docker is installed
if ! command -v docker &> /dev/null; then
    echo "❌ Docker is not installed. Please install Docker first."
    exit 1
fi

# Check if Docker Compose is installed
if ! command -v docker-compose &> /dev/null; then
    echo "❌ Docker Compose is not installed. Please install Docker Compose first."
    exit 1
fi

echo "✅ Docker and Docker Compose are installed"

# Function to build and run the application
build_and_run() {
    echo "🔨 Building Docker image..."
    docker-compose build
    
    if [ $? -eq 0 ]; then
        echo "✅ Docker image built successfully"
        
        echo "🚀 Starting the application..."
        docker-compose up -d
        
        if [ $? -eq 0 ]; then
            echo "✅ Application started successfully!"
            echo ""
            echo "🌐 Access your application at: http://localhost:8080"
            echo "📱 Mobile app entry point: http://localhost:8080/mobile-app/"
            echo ""
            echo "📋 Useful commands:"
            echo "   - View logs: docker-compose logs -f"
            echo "   - Stop app: docker-compose down"
            echo "   - Restart app: docker-compose restart"
            echo "   - Rebuild: docker-compose up --build"
        else
            echo "❌ Failed to start the application"
            exit 1
        fi
    else
        echo "❌ Failed to build Docker image"
        exit 1
    fi
}

# Function to stop the application
stop_app() {
    echo "🛑 Stopping the application..."
    docker-compose down
    echo "✅ Application stopped"
}

# Function to view logs
view_logs() {
    echo "📋 Viewing application logs..."
    docker-compose logs -f
}

# Function to clean up
cleanup() {
    echo "🧹 Cleaning up Docker resources..."
    docker-compose down -v --remove-orphans
    docker system prune -f
    echo "✅ Cleanup completed"
}

# Main menu
while true; do
    echo ""
    echo "Please select an option:"
    echo "1) Build and run the application"
    echo "2) Stop the application"
    echo "3) View logs"
    echo "4) Clean up Docker resources"
    echo "5) Exit"
    echo ""
    read -p "Enter your choice (1-5): " choice
    
    case $choice in
        1)
            build_and_run
            break
            ;;
        2)
            stop_app
            break
            ;;
        3)
            view_logs
            break
            ;;
        4)
            cleanup
            break
            ;;
        5)
            echo "👋 Goodbye!"
            exit 0
            ;;
        *)
            echo "❌ Invalid option. Please try again."
            ;;
    esac
done 