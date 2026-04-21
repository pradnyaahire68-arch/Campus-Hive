// CampusHive - Enhanced Main JavaScript File
// Handles dynamic functionality, API calls, and user interactions

// Global variables for map functionality
let map;
let userLocation;
let eventLocation;

// Initialize application when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Auto-hide alerts after 5 seconds with smooth animation
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });

    // Initialize components based on current page
    initializePageComponents();

    // Add loading states to buttons
    initializeButtonLoadingStates();

    // Initialize smooth scrolling
    initializeSmoothScrolling();

    // Add form validation enhancements
    initializeFormValidation();
});

// Initialize page-specific components
function initializePageComponents() {
    const currentPath = window.location.pathname;

    if (currentPath.includes('events.php')) {
        loadEvents();
        initializeEventFilters();
    } else if (currentPath.includes('event_details.php')) {
        initializeMap();
        initializeEventActions();
    } else if (currentPath.includes('dashboard.php')) {
        initializeDashboardComponents();
    }
}

// Submit form via AJAX
function submitForm(form) {
    const button = form.querySelector('button[type="submit"]');
    const originalText = button.innerHTML;
    const loadingText = button.getAttribute('data-loading-text') || 'Loading...';

    button.disabled = true;
    button.innerHTML = `<span class="spinner-border spinner-border-sm me-2" role="status"></span>${loadingText}`;

    const formData = new FormData(form);

    fetch(form.action || window.location.href, {
        method: form.method || 'POST',
        body: formData
    })
    .then(response => {
        if (response.redirected) {
            window.location.href = response.url;
        } else {
            return response.text();
        }
    })
    .then(data => {
        if (data) {
            // Failed, replace the body with the response
            document.body.innerHTML = data;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        button.disabled = false;
        button.innerHTML = originalText;
        showAlert('An error occurred. Please try again.', 'danger');
    });
}

// Initialize smooth scrolling
function initializeSmoothScrolling() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

// Initialize form validation
function initializeFormValidation() {
    const forms = document.querySelectorAll('form[data-validate]');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            if (validateForm(this)) {
                submitForm(this);
            } else {
                showFormErrors(this);
            }
        });
    });
}

// Form validation function
function validateForm(form) {
    let isValid = true;
    const requiredFields = form.querySelectorAll('[required]');

    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            isValid = false;
        } else {
            field.classList.remove('is-invalid');
            field.classList.add('is-valid');
        }
    });

    // Email validation
    const emailFields = form.querySelectorAll('input[type="email"]');
    emailFields.forEach(field => {
        if (field.value && !isValidEmail(field.value)) {
            field.classList.add('is-invalid');
            isValid = false;
        }
    });

    return isValid;
}

// Email validation helper
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Show form errors
function showFormErrors(form) {
    const errorDiv = document.createElement('div');
    errorDiv.className = 'alert alert-danger mt-3';
    errorDiv.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Please fill in all required fields correctly.';
    form.insertBefore(errorDiv, form.firstChild);

    setTimeout(() => {
        errorDiv.remove();
    }, 5000);
}

// Initialize dashboard components
function initializeDashboardComponents() {
    // Add fade-in animations to stats cards
    const statsCards = document.querySelectorAll('.stats-card');
    statsCards.forEach((card, index) => {
        setTimeout(() => {
            card.classList.add('fade-in');
        }, index * 200);
    });

    // Initialize sidebar if present
    initializeSidebar();
}

// Initialize sidebar functionality
function initializeSidebar() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    const sidebarToggle = document.getElementById('sidebarToggle');

    if (sidebarToggle && sidebar && mainContent) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('show');
            mainContent.classList.toggle('sidebar-open');
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(event.target) && !sidebarToggle.contains(event.target)) {
                    sidebar.classList.remove('show');
                    mainContent.classList.remove('sidebar-open');
                }
            }
        });
    }
}

// Initialize event filters
function initializeEventFilters() {
    const filterButtons = document.querySelectorAll('.filter-btn');
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Remove active class from all buttons
            filterButtons.forEach(btn => btn.classList.remove('active'));
            // Add active class to clicked button
            this.classList.add('active');

            const filter = this.textContent.toLowerCase().replace(' ', '');
            const searchQuery = document.getElementById('searchInput')?.value || '';
            loadEvents(filter, searchQuery);
        });
    });

    // Search input handler
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const currentFilter = document.querySelector('.filter-btn.active')?.textContent.toLowerCase().replace(' ', '') || 'all';
                loadEvents(currentFilter, this.value);
            }, 300);
        });
    }
}

// Load and display events with filtering
function loadEvents(filter = 'all', search = '') {
    const eventsContainer = document.getElementById('eventsContainer');
    if (!eventsContainer) return;

    // Show loading spinner with modern design
    eventsContainer.innerHTML = `
        <div class="col-12 text-center py-5">
            <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                <span class="visually-hidden">Loading...</span>
            </div>
            <h5 class="text-muted">Loading events...</h5>
            <p class="text-muted small">Please wait while we fetch the latest events</p>
        </div>
    `;

    fetch(`../api/fetch_events.php?filter=${filter}&search=${encodeURIComponent(search)}`)
        .then(response => response.json())
        .then(data => {
            displayEvents(data);
        })
        .catch(error => {
            console.error('Error loading events:', error);
            eventsContainer.innerHTML = `
                <div class="col-12">
                    <div class="alert alert-danger d-flex align-items-center">
                        <i class="fas fa-exclamation-triangle me-3 fs-4"></i>
                        <div>
                            <h5 class="alert-heading mb-1">Error Loading Events</h5>
                            <p class="mb-2">Please check your internet connection and try again.</p>
                            <button class="btn btn-primary" onclick="loadEvents('${filter}', '${search}')">
                                <i class="fas fa-redo me-2"></i>Retry
                            </button>
                        </div>
                    </div>
                </div>
            `;
        });
}

// Display events in cards
function displayEvents(events) {
    const container = document.getElementById('eventsContainer');
    container.innerHTML = '';

    if (events.length === 0) {
        container.innerHTML = `
            <div class="col-12">
                <div class="text-center py-5">
                    <div class="mb-4">
                        <i class="fas fa-calendar-times fa-4x text-muted"></i>
                    </div>
                    <h4 class="text-muted mb-3">No Events Found</h4>
                    <p class="text-muted mb-4">No events match your current criteria. Try adjusting your filters or search terms.</p>
                    <button class="btn btn-primary" onclick="resetFilters()">
                        <i class="fas fa-undo me-2"></i>Reset Filters
                    </button>
                </div>
            </div>
        `;
        return;
    }

    events.forEach((event, index) => {
        const statusClass = getStatusClass(event.status);
        const eventCard = `
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="event-card fade-in" style="animation-delay: ${index * 0.1}s">
                    ${event.image ? `<img src="../assets/images/${event.image}" class="card-img-top" alt="Event Image" onerror="this.style.display='none'">` : ''}
                    <div class="event-status ${statusClass}">${event.status.charAt(0).toUpperCase() + event.status.slice(1)}</div>
                    <div class="card-body">
                        <h5 class="card-title">${escapeHtml(event.title)}</h5>
                        <p class="card-text text-muted">${escapeHtml(event.description.substring(0, 100))}...</p>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <small class="text-muted d-block">
                                    <i class="fas fa-calendar me-1"></i>${new Date(event.event_date).toLocaleDateString()}
                                </small>
                            </div>
                            <div class="col-6">
                                <small class="text-muted d-block">
                                    <i class="fas fa-clock me-1"></i>${event.event_time}
                                </small>
                            </div>
                            <div class="col-6">
                                <small class="text-muted d-block">
                                    <i class="fas fa-university me-1"></i>${escapeHtml(event.college_name)}
                                </small>
                            </div>
                            <div class="col-6">
                                <small class="text-muted d-block">
                                    <i class="fas fa-users me-1"></i>${event.available_seats}/${event.total_seats} seats
                                </small>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <button class="btn btn-primary btn-sm" onclick="viewEventDetails(${event.event_id})">
                                <i class="fas fa-eye me-1"></i>View Details
                            </button>
                            ${event.status === 'upcoming' && event.available_seats > 0 ?
                                `<button class="btn btn-success btn-sm" onclick="registerForEvent(${event.event_id})" data-loading-text="Registering...">
                                    <i class="fas fa-plus me-1"></i>Register
                                </button>` :
                                event.available_seats === 0 ?
                                    '<span class="text-danger small"><i class="fas fa-ban me-1"></i>Fully Booked</span>' :
                                    ''
                            }
                        </div>
                    </div>
                </div>
            </div>
        `;
        container.innerHTML += eventCard;
    });
}

// Reset filters function
function resetFilters() {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) searchInput.value = '';

    const filterButtons = document.querySelectorAll('.filter-btn');
    filterButtons.forEach(btn => btn.classList.remove('active'));
    filterButtons[0]?.classList.add('active'); // Make 'All' active

    loadEvents('all', '');
}

// Get CSS class for event status
function getStatusClass(status) {
    switch(status) {
        case 'upcoming': return 'status-upcoming';
        case 'ongoing': return 'status-ongoing';
        case 'expired': return 'status-expired';
        default: return '';
    }
}

// View event details
function viewEventDetails(eventId) {
    window.location.href = `event_details.php?id=${eventId}`;
}

// Register for event with enhanced feedback
function registerForEvent(eventId) {
    if (confirm('Are you sure you want to register for this event?')) {
        showLoading('Registering for event...');

        fetch('../api/register_event.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ event_id: eventId })
        })
        .then(response => response.json())
        .then(data => {
            hideLoading();
            if (data.success) {
                showAlert('Registration successful! You can download your QR code from your dashboard.', 'success');
                // Reload events to update seat counts
                const currentFilter = document.querySelector('.filter-btn.active')?.textContent.toLowerCase().replace(' ', '') || 'all';
                const searchQuery = document.getElementById('searchInput')?.value || '';
                loadEvents(currentFilter, searchQuery);
            } else {
                showAlert('Registration failed: ' + data.message, 'danger');
            }
        })
        .catch(error => {
            hideLoading();
            console.error('Error registering for event:', error);
            showAlert('Error registering for event. Please try again.', 'danger');
        });
    }
}

// Initialize event actions on detail page
function initializeEventActions() {
    // Add any event-specific actions here
}

// Initialize Google Maps with enhanced features
function initializeMap() {
    const eventLat = parseFloat(document.getElementById('eventLat')?.value);
    const eventLng = parseFloat(document.getElementById('eventLng')?.value);

    if (!eventLat || !eventLng) return;

    const eventLocationLatLng = { lat: eventLat, lng: eventLng };

    // Initialize map with enhanced options
    map = new google.maps.Map(document.getElementById('map'), {
        zoom: 15,
        center: eventLocationLatLng,
        mapTypeControl: false,
        streetViewControl: false,
        fullscreenControl: false,
        styles: [
            {
                featureType: 'poi',
                stylers: [{ visibility: 'off' }]
            }
        ]
    });

    // Add event marker with custom icon
    const eventMarker = new google.maps.Marker({
        position: eventLocationLatLng,
        map: map,
        title: 'Event Location',
        icon: {
            url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
                <svg width="40" height="40" viewBox="0 0 40 40" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="20" cy="20" r="18" fill="#3182ce" stroke="white" stroke-width="3"/>
                    <path d="M20 8 L28 16 L20 24 L12 16 Z" fill="white"/>
                </svg>
            `),
            scaledSize: new google.maps.Size(40, 40)
        }
    });

    eventLocation = eventLocationLatLng;

    // Try to get user's location with better error handling
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            function(position) {
                userLocation = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude
                };

                // Add user marker
                const userMarker = new google.maps.Marker({
                    position: userLocation,
                    map: map,
                    title: 'Your Location',
                    icon: {
                        url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
                            <svg width="30" height="30" viewBox="0 0 30 30" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="15" cy="15" r="12" fill="#38a169" stroke="white" stroke-width="2"/>
                            </svg>
                        `),
                        scaledSize: new google.maps.Size(30, 30)
                    }
                });

                // Fit bounds to show both markers
                const bounds = new google.maps.LatLngBounds();
                bounds.extend(eventLocationLatLng);
                bounds.extend(userLocation);
                map.fitBounds(bounds);

                // Add directions button
                addDirectionsButton();
            },
            function(error) {
                console.warn('Error getting user location:', error);
                // Still show event marker even if user location fails
                map.setCenter(eventLocationLatLng);
                showAlert('Unable to get your location. Directions may not be available.', 'warning');
            }
        );
    } else {
        // Geolocation not supported
        map.setCenter(eventLocationLatLng);
        showAlert('Geolocation is not supported by this browser.', 'warning');
    }
}

// Add directions button to map
function addDirectionsButton() {
    const directionsButton = document.createElement('button');
    directionsButton.innerHTML = '<i class="fas fa-directions me-2"></i>Get Directions';
    directionsButton.className = 'btn btn-primary';
    directionsButton.style.cssText = 'position: absolute; top: 10px; right: 10px; z-index: 1000; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);';
    directionsButton.onclick = getDirections;

    document.getElementById('map').appendChild(directionsButton);
}

// Get directions to event
function getDirections() {
    if (!userLocation || !eventLocation) {
        showAlert('Unable to get directions. Please ensure location services are enabled.', 'warning');
        return;
    }

    const directionsUrl = `https://www.google.com/maps/dir/${userLocation.lat},${userLocation.lng}/${eventLocation.lat},${eventLocation.lng}`;
    window.open(directionsUrl, '_blank');
}

// Download QR code (placeholder)
function downloadQR() {
    showAlert('QR code download functionality will be implemented with a QR code generation library.', 'info');
}

// Utility function to escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Enhanced alert function
function showAlert(message, type = 'info') {
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alert.style.cssText = 'top: 20px; right: 20px; z-index: 10000; min-width: 300px; box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);';
    alert.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'danger' ? 'exclamation-triangle' : type === 'warning' ? 'exclamation-circle' : 'info-circle'} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(alert);

    // Auto remove after 5 seconds
    setTimeout(() => {
        if (alert.parentNode) {
            alert.remove();
        }
    }, 5000);
}

// Loading overlay functions
function showLoading(message = 'Loading...') {
    const loading = document.createElement('div');
    loading.id = 'loading-overlay';
    loading.innerHTML = `
        <div class="position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center" style="background: rgba(0,0,0,0.5); z-index: 9999;">
            <div class="bg-white rounded p-4 text-center shadow">
                <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div class="h5">${message}</div>
            </div>
        </div>
            `;
    document.body.appendChild(loading);
}

function hideLoading() {
    const loading = document.getElementById('loading-overlay');
    if (loading) {
        loading.remove();
    }
}

// Export functions for global access
window.loadEvents = loadEvents;
window.displayEvents = displayEvents;
window.viewEventDetails = viewEventDetails;
window.registerForEvent = registerForEvent;
window.resetFilters = resetFilters;
window.initializeMap = initializeMap;
window.downloadQR = downloadQR;
window.showAlert = showAlert;
window.showLoading = showLoading;
window.hideLoading = hideLoading;
