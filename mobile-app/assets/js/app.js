/**
 * Main JavaScript file for the Progressive Web App
 * Handles UI interactions, API calls, and offline functionality
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize UI components
    initializeModals();
    initializeFlashMessages();
    initializeNavigation();
    initializeOfflineDetection();
    initializeForms();
});

/**
 * Initialize modal functionality
 */
function initializeModals() {
    // Login modal
    const loginModal = document.getElementById('login-modal');
    const registerModal = document.getElementById('register-modal');
    const loginButtons = document.querySelectorAll('.login-btn');
    const registerLinks = document.querySelectorAll('.show-register');
    const loginLinks = document.querySelectorAll('.show-login');
    const closeButtons = document.querySelectorAll('.close');
    
    // Show login modal
    loginButtons.forEach(button => {
        button.addEventListener('click', (e) => {
            e.preventDefault();
            loginModal.style.display = 'block';
        });
    });
    
    // Show register modal
    registerLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            loginModal.style.display = 'none';
            registerModal.style.display = 'block';
        });
    });
    
    // Show login modal from register
    loginLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            registerModal.style.display = 'none';
            loginModal.style.display = 'block';
        });
    });
    
    // Close modals
    closeButtons.forEach(button => {
        button.addEventListener('click', () => {
            loginModal.style.display = 'none';
            registerModal.style.display = 'none';
        });
    });
    
    // Close when clicking outside the modal
    window.addEventListener('click', (e) => {
        if (e.target === loginModal) {
            loginModal.style.display = 'none';
        }
        if (e.target === registerModal) {
            registerModal.style.display = 'none';
        }
    });
}

/**
 * Initialize flash messages
 */
function initializeFlashMessages() {
    const flashContainer = document.getElementById('flash-messages');
    if (!flashContainer) return;
    
    const closeButtons = flashContainer.querySelectorAll('.close-flash');
    
    // Auto-hide flash messages after 5 seconds
    setTimeout(() => {
        const messages = flashContainer.querySelectorAll('.flash-message');
        messages.forEach(message => {
            message.style.opacity = '0';
            setTimeout(() => {
                message.remove();
            }, 500);
        });
    }, 5000);
    
    // Close button for flash messages
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const message = this.parentElement;
            message.style.opacity = '0';
            setTimeout(() => {
                message.remove();
            }, 500);
        });
    });
}

/**
 * Initialize navigation functionality
 */
function initializeNavigation() {
    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        if (!anchor.classList.contains('login-btn') && 
            !anchor.classList.contains('show-register') && 
            !anchor.classList.contains('show-login')) {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                
                const href = this.getAttribute('href');
                if (!href || href === '#') return; // Skip invalid selectors
                const target = document.querySelector(href);
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            });
        }
    });
    
    // Highlight active navigation link based on scroll position
    const sections = document.querySelectorAll('section');
    const navLinks = document.querySelectorAll('header nav ul li a');
    
    window.addEventListener('scroll', () => {
        let current = '';
        
        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            const sectionHeight = section.clientHeight;
            
            if (window.pageYOffset >= (sectionTop - 100)) {
                current = section.getAttribute('id');
            }
        });
        
        navLinks.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === '#' + current) {
                link.classList.add('active');
            }
            if (current === undefined && link.getAttribute('href') === '#') {
                link.classList.add('active');
            }
        });
    });
}

/**
 * Initialize offline detection
 */
function initializeOfflineDetection() {
    const appShell = document.getElementById('app-shell');
    
    // Check if online and update UI
    function updateOnlineStatus() {
        if (!appShell) return; // Prevent error if #app-shell is missing
        if (!navigator.onLine) {
            appShell.querySelector('.offline-message').style.display = 'block';
            document.body.classList.add('offline');
        } else {
            appShell.querySelector('.offline-message').style.display = 'none';
            document.body.classList.remove('offline');
        }
    }
    
    window.addEventListener('online', updateOnlineStatus);
    window.addEventListener('offline', updateOnlineStatus);
    
    // Initial check
    updateOnlineStatus();
}

/**
 * Initialize form submissions
 */
function initializeForms() {
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');
    
    // Login form submission
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            
            // Validate inputs
            if (!email || !password) {
                showFlashMessage('error', 'Email and password are required');
                return;
            }
            
            // Call the login API
            fetch('api/auth.php?login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    email: email,
                    password: password
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Save token to local storage for PWA
                    localStorage.setItem('token', data.token);
                    localStorage.setItem('user', JSON.stringify(data.user));
                    
                    // Show success message and redirect
                    showFlashMessage('success', 'Login successful');
                    document.getElementById('login-modal').style.display = 'none';
                    
                    // Reload page after short delay
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    showFlashMessage('error', data.error || 'Login failed');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showFlashMessage('error', 'An error occurred. Please try again.');
                
                // Try to use cached credentials if offline
                if (!navigator.onLine) {
                    tryOfflineLogin(email, password);
                }
            });
        });
    }
    
    // Register form submission
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const name = document.getElementById('reg-name').value;
            const email = document.getElementById('reg-email').value;
            const password = document.getElementById('reg-password').value;
            const confirmPassword = document.getElementById('reg-confirm-password').value;
            
            // Validate inputs
            if (!name || !email || !password || !confirmPassword) {
                showFlashMessage('error', 'All fields are required');
                return;
            }
            
            if (password !== confirmPassword) {
                showFlashMessage('error', 'Passwords do not match');
                return;
            }
            
            // Call the register API
            fetch('api/auth.php?register', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    name: name,
                    email: email,
                    password: password
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showFlashMessage('success', 'Registration successful. Please login.');
                    document.getElementById('register-modal').style.display = 'none';
                    document.getElementById('login-modal').style.display = 'block';
                    
                    // Pre-fill login form
                    document.getElementById('email').value = email;
                } else {
                    showFlashMessage('error', data.error || 'Registration failed');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showFlashMessage('error', 'An error occurred. Please try again.');
            });
        });
    }
}

/**
 * Try offline login with cached credentials
 * @param {string} email User email
 * @param {string} password User password
 */
function tryOfflineLogin(email, password) {
    // Check if we have a cached user
    const cachedUser = localStorage.getItem('user');
    const cachedEmail = cachedUser ? JSON.parse(cachedUser).email : null;
    
    if (cachedEmail && cachedEmail === email) {
        showFlashMessage('info', 'Using cached login information while offline');
        
        // Simulate successful login
        document.getElementById('login-modal').style.display = 'none';
        
        // Reload page after short delay
        setTimeout(() => {
            window.location.reload();
        }, 1000);
    } else {
        showFlashMessage('error', 'Cannot login while offline with new credentials');
    }
}

/**
 * Display a flash message
 * @param {string} type Message type (success, error, info, warning)
 * @param {string} message Message content
 */
function showFlashMessage(type, message) {
    // Create flash message container if it doesn't exist
    let flashContainer = document.getElementById('flash-messages');
    if (!flashContainer) {
        flashContainer = document.createElement('div');
        flashContainer.id = 'flash-messages';
        document.body.appendChild(flashContainer);
    }
    
    // Create message element
    const flashMessage = document.createElement('div');
    flashMessage.className = `flash-message ${type}`;
    flashMessage.innerHTML = `
        ${message}
        <span class="close-flash">&times;</span>
    `;
    
    // Add to container
    flashContainer.appendChild(flashMessage);
    
    // Add close functionality
    const closeButton = flashMessage.querySelector('.close-flash');
    closeButton.addEventListener('click', function() {
        flashMessage.style.opacity = '0';
        setTimeout(() => {
            flashMessage.remove();
        }, 500);
    });
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        flashMessage.style.opacity = '0';
        setTimeout(() => {
            flashMessage.remove();
        }, 500);
    }, 5000);
} 