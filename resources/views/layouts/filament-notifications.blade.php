<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gmail Notifications</title>
    <script>
        // Browser Notification Support
        class GmailNotificationManager {
            constructor() {
                this.checkNotificationSupport();
                this.requestPermission();
                this.setupEventListeners();
            }

            checkNotificationSupport() {
                if (!("Notification" in window)) {
                    console.log("This browser does not support desktop notification");
                    return false;
                }
                return true;
            }

            async requestPermission() {
                if (Notification.permission === "default") {
                    const permission = await Notification.requestPermission();
                    console.log("Notification permission:", permission);
                }
            }

            showNotification(title, options = {}) {
                if (Notification.permission === "granted") {
                    const notification = new Notification(title, {
                        icon: '/images/voltmaster-favicon.svg',
                        badge: '/images/voltmaster-favicon.svg',
                        ...options
                    });

                    // Auto-close after 5 seconds
                    setTimeout(() => {
                        notification.close();
                    }, 5000);

                    // Handle click
                    notification.onclick = function() {
                        window.focus();
                        window.location.href = '/admin/notifications';
                        notification.close();
                    };

                    return notification;
                }
            }

            setupEventListeners() {
                // Listen for new Gmail notifications via polling
                this.startPolling();
            }

            async startPolling() {
                let lastNotificationCount = 0;
                
                const checkForNewNotifications = async () => {
                    try {
                        const response = await fetch('/api/notifications/count');
                        const data = await response.json();
                        
                        if (data.unread_count > lastNotificationCount && lastNotificationCount > 0) {
                            const newCount = data.unread_count - lastNotificationCount;
                            this.showNotification(
                                `${newCount} neue Gmail E-Mail${newCount > 1 ? 's' : ''}`,
                                {
                                    body: 'Klicken Sie hier, um die Benachrichtigungen anzuzeigen',
                                    tag: 'gmail-notification'
                                }
                            );
                        }
                        
                        lastNotificationCount = data.unread_count;
                    } catch (error) {
                        console.error('Error checking notifications:', error);
                    }
                };

                // Initial check
                checkForNewNotifications();
                
                // Poll every 30 seconds
                setInterval(checkForNewNotifications, 30000);
            }
        }

        // Initialize when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            const notificationManager = new GmailNotificationManager();
            window.gmailNotifications = notificationManager;
        });
    </script>
</head>
<body>
    <!-- This template provides JavaScript for Gmail notifications -->
</body>
</html>
