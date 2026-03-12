# Hospital Management System

A web-based Hospital Management System built using **PHP and MySQL**.
This application allows doctors and patients to manage appointments, medical records, and payments through a simple web interface.

---

## Features

* User authentication (login system)
* Doctor registration
* Patient registration
* Appointment booking system
* Medical records management
* Payment management
* Separate dashboards for doctors and patients

---

## Tech Stack

Frontend

* HTML
* CSS
* JavaScript

Backend

* PHP

Database

* MySQL

Environment

* XAMPP / Apache Server

---

## Installation Guide

### 1. Clone the repository

```
git clone https://github.com/tintinbunyispeda/hospital-management-system.git
```

### 2. Move project to XAMPP htdocs

Example path:

```
C:\xampp\htdocs\hospital-management-system
```

### 3. Start XAMPP

Start the following services:

* Apache
* MySQL

### 4. Import database

1. Open phpMyAdmin
2. Create a new database (example: `hospital_db`)
3. Import the file:

```
db.sql
```

### 5. Configure database connection

Open:

```
config.php
```

Make sure the database configuration matches your setup.

Example:

```
$conn = mysqli_connect("localhost","root","","hospital_db");
```

### 6. Run the project

Open in browser:

```
http://localhost/hospital-management-system
```

or

```
http://localhost/hospital-management-system/login.php
```

---

## Project Structure

```
hospital-management-system
│
├── login.php
├── register_doctor.php
├── register_patient.php
├── patient_dashboard.php
├── doctor_dashboard.php
├── appointments.php
├── medical_records.php
├── payments.php
├── config.php
├── db.sql
└── README.md
```

---

## Future Improvements

* Improve UI design with modern CSS framework
* Add role-based authentication
* Implement appointment calendar view
* Convert frontend to React
* Add REST API backend

---

## Author
# Hospital Management System

A web-based Hospital Management System built using **PHP and MySQL**.
This application allows doctors and patients to manage appointments, medical records, and payments through a simple web interface.

---

## Features

* User authentication (login system)
* Doctor registration
* Patient registration
* Appointment booking system
* Medical records management
* Payment management
* Separate dashboards for doctors and patients

---

## Tech Stack

Frontend

* HTML
* CSS
* JavaScript

Backend

* PHP

Database

* MySQL

Environment

* XAMPP / Apache Server

---

## Installation Guide

### 1. Clone the repository

```
git clone https://github.com/tintinbunyispeda/hospital-management-system.git
```

### 2. Move project to XAMPP htdocs

Example path:

```
C:\xampp\htdocs\hospital-management-system
```

### 3. Start XAMPP

Start the following services:

* Apache
* MySQL

### 4. Import database

1. Open phpMyAdmin
2. Create a new database (example: `hospital_db`)
3. Import the file:

```
db.sql
```

### 5. Configure database connection

Open:

```
config.php
```

Make sure the database configuration matches your setup.

Example:

```
$conn = mysqli_connect("localhost","root","","hospital_db");
```

### 6. Run the project

Open in browser:

```
http://localhost/hospital-management-system
```

or

```
http://localhost/hospital-management-system/login.php
```

---

## Project Structure

```
hospital-management-system
│
├── login.php
├── register_doctor.php
├── register_patient.php
├── patient_dashboard.php
├── doctor_dashboard.php
├── appointments.php
├── medical_records.php
├── payments.php
├── config.php
├── db.sql
└── README.md
```

---

## Future Improvements

* Improve UI design with modern CSS framework
* Add role-based authentication
* Implement appointment calendar view
* Convert frontend to React
* Add REST API backend

---

## Author

Cristine Valentina


