# Real-Time Donation Status Update System

## 🎯 **Overview**

This system automatically updates donation statuses in real-time when staff modify forms in your separate blood management system. It eliminates the need for manual status updates and ensures donors always see their current progress.

## 🚀 **How It Works**

### **1. Automatic Status Detection**
- **Screening Form Updates**: When staff complete screening forms, status automatically updates to "Sample Collected"
- **Physical Examination Updates**: When staff complete physical exams, status automatically updates to "Testing"
- **Blood Collection Updates**: When staff complete blood collection, status automatically updates to "Processed"
- **Real-time Updates**: Status changes happen immediately without manual intervention

### **2. Multi-System Integration**
- **Web Mobile App**: Donors submit medical history and see real-time updates
- **Blood Management System**: Staff update forms in separate system
- **Shared Database**: Both systems use the same database for seamless integration
- **Automatic Sync**: Status updates happen automatically when forms are modified

## 📋 **Setup Instructions**

### **Option 1: Cron Job (Recommended for Production)**

#### **Step 1: Set up Cron Job**
```bash
# Edit crontab
crontab -e

# Add this line to run every 2 minutes
*/2 * * * * /usr/bin/php /path/to/your/project/mobile-app/cron_update_donations.php
```

#### **Step 2: Test Cron Job**
```bash
# Test the script manually
php /path/to/your/project/mobile-app/cron_update_donations.php

# Check the log file
tail -f /path/to/your/project/mobile-app/cron_donation_updates.log
```

### **Option 2: HTTP Endpoint (Alternative)**

#### **Step 1: Set up External Cron Service**
Use services like:
- **Cron-job.org** (Free)
- **EasyCron** (Paid)
- **Your hosting provider's cron service**

#### **Step 2: Configure URL**
```
https://yoursite.com/mobile-app/cron_update_donations.php
```

#### **Step 3: Set Schedule**
- **Frequency**: Every 2-5 minutes
- **Method**: GET request
- **Timeout**: 5 minutes

### **Option 3: Webhook Integration (Advanced)**

#### **Step 1: Modify Blood Management System**
Add webhook calls when forms are updated:
```php
// After updating screening_form, physical_examination, or blood_collection
$webhook_url = 'https://yoursite.com/mobile-app/api/auto_status_update.php';
$data = ['action' => 'update_specific_donor', 'donor_id' => $donor_id];

$ch = curl_init($webhook_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_exec($ch);
curl_close($ch);
```

## 🔧 **API Endpoints**

### **1. Update All Donations**
```
GET/POST: /mobile-app/api/auto_status_update.php?action=update_all
```
**Use**: Cron jobs, system maintenance

### **2. Update Specific Donor**
```
GET/POST: /mobile-app/api/auto_status_update.php?action=update_specific_donor&donor_id=123
```
**Use**: Webhook calls, manual updates

### **3. Update by Email**
```
GET/POST: /mobile-app/api/auto_status_update.php?action=update_by_email&email=donor@example.com
```
**Use**: User-triggered updates

### **4. Check Donor Status**
```
GET/POST: /mobile-app/api/auto_status_update.php?action=check_donor_status&donor_id=123
```
**Use**: Status verification, debugging

## 📱 **User Experience**

### **For Donors:**
1. **Submit Medical History** → Status: "Registered"
2. **Staff Complete Screening** → Status automatically updates to "Sample Collected"
3. **Staff Complete Physical Exam** → Status automatically updates to "Testing"
4. **Staff Complete Blood Collection** → Status automatically updates to "Processed"
5. **Real-time Updates** → Status changes visible immediately

### **For Staff:**
1. **Update forms** in blood management system
2. **Status updates automatically** in donor tracker
3. **No manual intervention** required
4. **Real-time synchronization** between systems

## 🧪 **Testing the System**

### **Test 1: Manual Status Update**
```bash
# Test updating a specific donor
curl "https://yoursite.com/mobile-app/api/auto_status_update.php?action=update_specific_donor&donor_id=123"
```

### **Test 2: Update by Email**
```bash
# Test updating by email
curl "https://yoursite.com/mobile-app/api/auto_status_update.php?action=update_by_email&email=donor@example.com"
```

### **Test 3: Update All Donations**
```bash
# Test updating all donations
curl "https://yoursite.com/mobile-app/api/auto_status_update.php?action=update_all"
```

## 📊 **Monitoring and Logs**

### **Log File Location**
```
mobile-app/cron_donation_updates.log
```

### **Log Format**
```
[2025-08-24 21:30:00] Starting automatic donation status update process...
[2025-08-24 21:30:01] Update process completed successfully!
[2025-08-24 21:30:01] Total donations checked: 5
[2025-08-24 21:30:01] Total donations updated: 2
[2025-08-24 21:30:01] Total errors: 0
[2025-08-24 21:30:01] Update details:
[2025-08-24 21:30:01]   - Donor ID 123: Registered → Sample Collected
[2025-08-24 21:30:01]   - Donor ID 456: Sample Collected → Testing
```

## ⚠️ **Troubleshooting**

### **Common Issues:**

#### **1. Status Not Updating**
- Check if cron job is running: `crontab -l`
- Verify log file permissions: `chmod 666 cron_donation_updates.log`
- Check database connectivity
- Verify form data exists in database

#### **2. Cron Job Not Working**
- Check cron service: `service cron status`
- Verify PHP path: `which php`
- Check file permissions
- Test script manually first

#### **3. API Endpoints Not Responding**
- Check file permissions
- Verify database configuration
- Check error logs
- Test with simple GET request

### **Debug Commands:**
```bash
# Check cron job status
crontab -l

# Check cron service
service cron status

# Test script manually
php /path/to/cron_update_donations.php

# Check log file
tail -f cron_donation_updates.log

# Check file permissions
ls -la cron_update_donations.php
```

## 🔒 **Security Considerations**

### **1. API Protection**
- Consider adding API keys for external calls
- Implement rate limiting
- Add IP whitelisting for cron jobs

### **2. Database Security**
- Use read-only database user for status checks
- Implement proper error handling
- Log all update attempts

### **3. File Permissions**
- Restrict access to cron scripts
- Secure log files
- Use proper file ownership

## 📈 **Performance Optimization**

### **1. Update Frequency**
- **Development**: Every 1 minute
- **Production**: Every 2-5 minutes
- **High Volume**: Every 10 minutes

### **2. Batch Processing**
- Process multiple donors in batches
- Use database transactions for updates
- Implement queue system for large datasets

### **3. Caching**
- Cache donor information
- Store update timestamps
- Implement smart update detection

## 🎉 **Benefits**

### **For Donors:**
- ✅ **Real-time updates** without manual refresh
- ✅ **Immediate feedback** on donation progress
- ✅ **Accurate tracking** of current status
- ✅ **Better user experience**

### **For Staff:**
- ✅ **No manual status updates** required
- ✅ **Automatic synchronization** between systems
- ✅ **Reduced errors** in status tracking
- ✅ **Focused on core tasks**

### **For System:**
- ✅ **Real-time data consistency**
- ✅ **Automated workflow** management
- ✅ **Scalable architecture**
- ✅ **Reliable status tracking**

## 🚀 **Next Steps**

1. **Choose setup option** (Cron job recommended)
2. **Configure update frequency** (2-5 minutes)
3. **Test with sample data**
4. **Monitor logs** for any issues
5. **Deploy to production**
6. **Train staff** on new workflow

Your donation tracking system will now work automatically in real-time! 🎉
