/**
 * This file contains a function to generate the Red Cross logo using canvas.
 * It's a temporary solution until the actual logo file is available.
 */

document.addEventListener('DOMContentLoaded', function() {
    // Find all image elements with the red cross logo class or src
    const logoElements = document.querySelectorAll('img[src*="red-cross-logo.png"], .logo');
    
    // Loop through each element and replace with the canvas-generated logo
    logoElements.forEach(function(imgElement) {
        const canvas = document.createElement('canvas');
        const width = imgElement.width || 150;
        const height = imgElement.height || 150;
        
        canvas.width = width;
        canvas.height = height;
        
        const ctx = canvas.getContext('2d');
        
        // Draw the outer circle (navy blue)
        ctx.beginPath();
        ctx.arc(width/2, height/2, width/2 - 2, 0, 2 * Math.PI);
        ctx.fillStyle = '#192f5d'; // Navy blue
        ctx.fill();
        
        // Draw the inner circle (white)
        ctx.beginPath();
        ctx.arc(width/2, height/2, width/2 - 10, 0, 2 * Math.PI);
        ctx.fillStyle = 'white';
        ctx.fill();
        
        // Draw the text "PHILIPPINE" at top
        ctx.font = `bold ${width/10}px Arial`;
        ctx.fillStyle = '#192f5d';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText('PHILIPPINE', width/2, height/4);
        
        // Draw the text "RED CROSS" at bottom
        ctx.fillText('RED CROSS', width/2, height * 3/4);
        
        // Draw the red cross
        ctx.fillStyle = '#e30613'; // Red
        
        // Horizontal rectangle
        ctx.fillRect(width/4, height/2 - width/10, width/2, width/5);
        
        // Vertical rectangle
        ctx.fillRect(width/2 - width/10, height/4, width/5, width/2);
        
        // Replace the image with the canvas
        const dataURL = canvas.toDataURL('image/png');
        imgElement.src = dataURL;
    });
}); 