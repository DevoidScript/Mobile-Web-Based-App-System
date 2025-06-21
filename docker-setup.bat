@echo off
setlocal enabledelayedexpansion

echo 🚀 Blood Donation App Docker Setup
echo ==================================

REM Check if Docker is installed
docker --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ Docker is not installed. Please install Docker Desktop first.
    echo Download from: https://www.docker.com/products/docker-desktop
    pause
    exit /b 1
)

REM Check if Docker Compose is installed
docker-compose --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ Docker Compose is not installed. Please install Docker Compose first.
    pause
    exit /b 1
)

echo ✅ Docker and Docker Compose are installed

:menu
echo.
echo Please select an option:
echo 1) Build and run the application
echo 2) Stop the application
echo 3) View logs
echo 4) Clean up Docker resources
echo 5) Exit
echo.
set /p choice="Enter your choice (1-5): "

if "%choice%"=="1" goto build_and_run
if "%choice%"=="2" goto stop_app
if "%choice%"=="3" goto view_logs
if "%choice%"=="4" goto cleanup
if "%choice%"=="5" goto exit
echo ❌ Invalid option. Please try again.
goto menu

:build_and_run
echo 🔨 Building Docker image...
docker-compose build
if %errorlevel% neq 0 (
    echo ❌ Failed to build Docker image
    pause
    exit /b 1
)

echo ✅ Docker image built successfully
echo 🚀 Starting the application...
docker-compose up -d
if %errorlevel% neq 0 (
    echo ❌ Failed to start the application
    pause
    exit /b 1
)

echo ✅ Application started successfully!
echo.
echo 🌐 Access your application at: http://localhost:8080
echo 📱 Mobile app entry point: http://localhost:8080/mobile-app/
echo.
echo 📋 Useful commands:
echo    - View logs: docker-compose logs -f
echo    - Stop app: docker-compose down
echo    - Restart app: docker-compose restart
echo    - Rebuild: docker-compose up --build
pause
goto menu

:stop_app
echo 🛑 Stopping the application...
docker-compose down
echo ✅ Application stopped
pause
goto menu

:view_logs
echo 📋 Viewing application logs...
echo Press Ctrl+C to exit logs view
docker-compose logs -f
pause
goto menu

:cleanup
echo 🧹 Cleaning up Docker resources...
docker-compose down -v --remove-orphans
docker system prune -f
echo ✅ Cleanup completed
pause
goto menu

:exit
echo 👋 Goodbye!
pause
exit /b 0 