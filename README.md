# Raven Fire Protection Website

The official website for **Raven Fire Protection**, built as a custom PHP-based website with a responsive frontend and server-side form processing. The project uses a primarily frontend-focused structure with PHP handling server-side functionality such as form submissions and email delivery.

## Features

* Responsive design for desktop, tablet, and mobile devices
* Contact form
* Multi-step service request form
* File attachments through service requests
* Server-side form validation
* Honeypot protection against basic automated submissions
* Email notifications for submitted forms
* Client-side form validation and interaction
* Animated UI components and responsive navigation
* Custom modal-based service request interface

## Tech Stack

### Frontend

* HTML5
* CSS3
* JavaScript
* Bootstrap
* Bootstrap Icons
* AOS (Animate On Scroll)
* Swiper
* GLightbox
* Isotope
* ImagesLoaded

### Backend

* PHP
* Composer
* PHPMailer
* Microsoft 365 / Outlook SMTP
* Microsoft Entra ID OAuth 2.0

### Hosting

The production website is hosted on a **DigitalOcean** server running Apache.


## Email System

Form submissions are processed server-side using PHP and PHPMailer.

The mail system uses Microsoft 365 authentication through Microsoft Entra ID OAuth 2.0.

Sensitive configuration values such as tenant IDs, client IDs, and client secrets are stored outside of the repository.


## Status

This project is actively maintained for Raven Fire Protection.

Production website:

https://ravenfp.com
