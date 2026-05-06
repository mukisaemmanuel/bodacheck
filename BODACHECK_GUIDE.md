# BodaCheck Learning Guide

Welcome to the BodaCheck codebase! This guide will help you understand how the project is structured, how the database works, and how the core features are implemented. 

## 1. Project Overview
BodaCheck is a digital compliance and safety ID system for boda boda riders. It allows riders to register, get a unique QR code, and allows traffic officers to scan that QR code to verify compliance (helmet, licence, insurance) and log violations.

## 2. Directory Structure
The project is organized into several key folders:

- `/includes/`: Contains core reusable files.
  - `db.php`: Connects to the MySQL database using PDO.
  - `session.php`: Manages user login sessions and checks roles (`isRider()`, `isOfficer()`, `isAdmin()`).
  - `functions.php`: Contains helper functions like `calculateStatus()`, `updateRiderScore()`, and `logScan()`.
  - `header.php` / `footer.php`: The top and bottom HTML used across the site.
- `/libs/`: External or standalone libraries.
  - `qrlib.php`: Connects to the QR Server API to generate QR code images and saves them locally.
- `/assets/`: Static files.
  - `/css/style.css`: All the styling for the website.
  - `/qr_codes/`: Where the generated PNG QR codes for riders are saved.
- `/rider/`: Pages specific to the boda boda rider.
  - `dashboard.php`: The rider's main view showing their safety score, QR code, and violation history.
- `/officer/`: Pages for traffic officers.
  - `scan.php`: The tool officers use to search for a rider or scan their QR code.
  - `log_violation.php`: The form officers use to penalize riders (deducts points).
- `/admin/`: Pages for government/KCCA oversight.
  - `dashboard.php`: A high-level overview of total riders, compliance rates, and revenue.

## 3. Database Schema
The database connects everything together. Here are the main tables:

1. `users`: Stores Officers and Admins. They log in with an email and password.
2. `saccos`: Stores the different Boda SACCO groups that riders belong to.
3. `riders`: The most important table. Stores rider details (Name, Phone, Bike Plate, QR Token, Safety Score, Status). Riders log in with their phone number.
4. `violations`: Records every time an officer logs an offense (e.g., "No Helmet"). Deducts points from the rider.
5. `scan_logs`: Records every time a rider's QR code is scanned, creating a digital paper trail.
6. `payments`: Tracks Mobile Money payments for annual registration.

## 4. Key Workflows to Study

### Registration & QR Code Generation
Look at `register.php`. When a rider registers:
1. It validates their data.
2. It generates a unique `qr_token` (e.g., RDR-1234abcd).
3. It saves the rider to the database.
4. *Note:* The actual QR image is generated the first time the rider visits their `rider/dashboard.php`.

### The Scoring System
Look at `includes/functions.php`.
- Every rider starts with 100 points (`safety_score`).
- Statuses: Green (70-100), Amber (40-69), Red (0-39).
- When an officer submits `officer/log_violation.php`, it calls `updateRiderScore()`. This function deducts points and automatically downgrades the rider's status color if their score drops too low.

### Session Management
Look at `login.php` and `includes/session.php`.
- The system uses PHP `$_SESSION` to remember who is logged in.
- Depending on whether you use the "Rider" tab or "Officer/Admin" tab, it checks different tables (`riders` vs `users`).
- Pages like `rider/dashboard.php` use `requireRole('rider');` at the very top to prevent unauthorized access.

## 5. How to Learn
1. **Start with the frontend:** Open `index.php` and follow the links to see how pages connect.
2. **Follow the data:** Register a test rider, then look at your database (via phpMyAdmin) to see exactly how the row was created in the `riders` table.
3. **Trace a feature:** Try logging a violation as an officer. Read `officer/log_violation.php` line-by-line to see how it inserts into the `violations` table and then updates the `riders` table.


🟩 Green Flag (Safe to Ride - Clean Record): http://localhost/bodacheck/scan.php?token=RDR-3bfc69f0ece223929fbbcd9545a8eb50

🟧 Amber Flag (Caution - Minor Violations): http://localhost/bodacheck/scan.php?token=RDR-e04800ac2ad27c63de35e269b053d610

🟥 Red Flag (Not Safe - Expired Insurance/Major Violations): http://localhost/bodacheck/scan.php?token=RDR-d8fc903387447cfaea0cb092ee98868c