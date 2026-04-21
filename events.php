<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'student') {
    header('Location: ../auth/login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Events - CampusHive</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="../index.php">CampusHive</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="events.php">Browse Events</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../auth/logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <h2>Browse Events</h2>
            </div>
        </div>

        <!-- Filter and Search -->
        <div class="row mt-3">
            <div class="col-md-12">
                <div class="filter-buttons">
                    <button class="btn btn-outline-primary filter-btn active" onclick="filterEvents('all')">All Events</button>
                    <button class="btn btn-outline-success filter-btn" onclick="filterEvents('upcoming')">Upcoming</button>
                    <button class="btn btn-outline-warning filter-btn" onclick="filterEvents('ongoing')">Ongoing</button>
                    <button class="btn btn-outline-danger filter-btn" onclick="filterEvents('expired')">Expired</button>
                </div>

                <div class="input-group mb-3">
                    <input type="text" class="form-control" id="searchInput" placeholder="Search events by title, college, or category...">
                    <button class="btn btn-outline-secondary" type="button" onclick="searchEvents()">Search</button>
                </div>
            </div>
        </div>

        <!-- Events Container -->
        <div class="row" id="eventsContainer">
            <!-- Events will be loaded here dynamically -->
        </div>
    </div>

    <!-- Event Details Modal -->
    <div class="modal fade" id="eventModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="eventModalTitle"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="eventModalBody">
                    <!-- Event details will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
    <script>
        let currentFilter = 'all';
        let searchQuery = '';

        document.addEventListener('DOMContentLoaded', function() {
            loadEvents();

            // Search on enter key
            document.getElementById('searchInput').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    searchEvents();
                }
            });
        });

        function filterEvents(filter) {
            currentFilter = filter;
            document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            loadEvents();
        }

        function searchEvents() {
            searchQuery = document.getElementById('searchInput').value.trim();
            loadEvents();
        }

        function loadEvents() {
            fetch(`../api/fetch_events.php?filter=${currentFilter}&search=${encodeURIComponent(searchQuery)}`)
                .then(response => response.json())
                .then(data => {
                    displayEvents(data);
                })
                .catch(error => {
                    console.error('Error loading events:', error);
                    document.getElementById('eventsContainer').innerHTML = '<div class="col-12"><div class="alert alert-danger">Error loading events. Please try again.</div></div>';
                });
        }

        function displayEvents(events) {
            const container = document.getElementById('eventsContainer');
            container.innerHTML = '';

            if (events.length === 0) {
                container.innerHTML = '<div class="col-12"><div class="alert alert-info">No events found matching your criteria.</div></div>';
                return;
            }

            events.forEach(event => {
                const statusClass = getStatusClass(event.status);
                const eventCard = `
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card event-card">
                            ${event.image ? `<img src="../assets/images/${event.image}" class="card-img-top" alt="Event Image">` : ''}
                            <div class="event-status ${statusClass}">${event.status}</div>
                            <div class="card-body">
                                <h5 class="card-title">${event.title}</h5>
                                <p class="card-text">${event.description.substring(0, 100)}...</p>
                                <p class="card-text"><small class="text-muted">Date: ${new Date(event.event_date).toLocaleDateString()} | Time: ${event.event_time}</small></p>
                                <p class="card-text"><small class="text-muted">College: ${event.college_name}</small></p>
                                <p class="card-text"><small class="text-muted">Seats Available: ${event.available_seats}/${event.total_seats}</small></p>
                                <div class="d-flex justify-content-between">
                                    <button class="btn btn-primary btn-sm" onclick="viewEventDetails(${event.event_id})">View Details</button>
                                    ${event.status === 'upcoming' && event.available_seats > 0 ? `<button class="btn btn-success btn-sm" onclick="registerForEvent(${event.event_id})">Register</button>` : ''}
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                container.innerHTML += eventCard;
            });
        }

        function getStatusClass(status) {
            switch(status) {
                case 'upcoming': return 'status-upcoming';
                case 'ongoing': return 'status-ongoing';
                case 'expired': return 'status-expired';
                default: return '';
            }
        }

        function viewEventDetails(eventId) {
            // This would load event details in a modal
            window.location.href = `event_details.php?id=${eventId}`;
        }

function registerForEvent(eventId) {
            if (confirm('Check event details first and confirm registration by paying the college fee if required.')) {
                window.location.href = `event_details.php?id=${eventId}`;
            }
        }
    </script>
</body>
</html>
