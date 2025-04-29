<?php
/**
 * Main entry point for the Red Cross Mobile App
 * Displays the login page as the main landing page
 */

// Include configuration files
require_once 'config/database.php';
require_once 'includes/functions.php';

// Start session if not already started
session_start();

// Include the login template
include 'templates/login.php'; 