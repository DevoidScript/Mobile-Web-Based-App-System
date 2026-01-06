# ngrok Setup Guide

## Step 1: Sign Up
1. Visit: https://dashboard.ngrok.com/signup
2. Create a free account (email or social login)
3. Verify your email if needed

## Step 2: Get Your Authtoken
1. Log in to: https://dashboard.ngrok.com
2. Go to: https://dashboard.ngrok.com/get-started/your-authtoken
3. Copy your authtoken (long string)

## Step 3: Configure ngrok
Open Command Prompt or PowerShell and run:

```bash
ngrok config add-authtoken YOUR_AUTHTOKEN_HERE
```

Replace `YOUR_AUTHTOKEN_HERE` with the token you copied.

## Step 4: Start ngrok
After authentication, run:

```bash
ngrok http 80
```

## Step 5: Get Your Public URL
Look for the line that says:
```
Forwarding    https://abc123.ngrok.io -> http://localhost:80
```

Copy the `https://abc123.ngrok.io` URL - this is what you'll use in Median.co!

## Notes:
- Keep the ngrok window open while testing
- The free plan gives you a random URL each time
- For permanent URL, you need a paid plan (but free is fine for testing)




