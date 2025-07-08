<script>
    // Browser Notification Support - Alpine.js compatible
    (function() {
        'use strict';
        
        // Avoid conflicts with Alpine.js by using a different initialization approach
        if (typeof window.gmailNotifications !== 'undefined') {
            return; // Already initialized
        }

        class GmailNotificationManager {
            constructor() {
                this.initialized = false;
                this.init();
            }

            async init() {
                if (this.initialized) return;
                
                // Wait for Alpine.js to be ready if it exists
                if (typeof window.Alpine !== 'undefined') {
                    await new Promise(resolve => {
                        if (window.Alpine.version) {
                            resolve();
                        } else {
                            document.addEventListener('alpine:init', resolve, { once: true });
                        }
                    });
                }
                
                this.checkNotificationSupport();
                await this.requestPermission();
                this.setupEventListeners();
                this.initialized = true;
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
                    try {
                        const permission = await Notification.requestPermission();
                        console.log("Notification permission:", permission);
                        return permission;
                    } catch (error) {
                        console.error("Error requesting notification permission:", error);
                        return "denied";
                    }
                }
                return Notification.permission;
            }

            showNotification(title, options = {}) {
                if (Notification.permission === "granted") {
                    try {
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
                    } catch (error) {
                        console.error("Error showing notification:", error);
                    }
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
                        if (!response.ok) {
                            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                        }
                        
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

                // Initial check after a short delay
                setTimeout(checkForNewNotifications, 2000);
                
                // Poll every 30 seconds
                setInterval(checkForNewNotifications, 30000);
            }
        }

        // Initialize with proper timing
        function initializeGmailNotifications() {
            if (typeof window.gmailNotifications === 'undefined') {
                window.gmailNotifications = new GmailNotificationManager();
            }
        }

        // Use multiple initialization strategies to ensure compatibility
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initializeGmailNotifications);
        } else if (document.readyState === 'interactive') {
            // DOM is ready but resources might still be loading
            setTimeout(initializeGmailNotifications, 100);
        } else {
            // DOM and resources are ready
            initializeGmailNotifications();
        }

        // Also listen for Alpine.js initialization if present
        document.addEventListener('alpine:init', function() {
            setTimeout(initializeGmailNotifications, 500);
        });

    })();
</script>
