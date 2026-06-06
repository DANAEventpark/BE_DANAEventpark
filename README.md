# DANAEventpark - Backend API (Laravel)

Welcome to the Backend Repository of **DANAEventpark**! This is the core of the entire system, handling all business logic, database interactions, and providing RESTful APIs for both the Attendee and Organizer frontend applications.

## 🌟 Overview & Key Features
The backend is built on the **Laravel (PHP)** framework. It manages key workflows including:
- User Authentication (Login, Register, Social Login)
- Event Management (CRUD for organizers)
- Categories Management
- Registrations & Ticketing (Waitlist & FIFO promotion mechanism)
- Reviews & Ratings system

---

## 💻 Technology Stack
- **Framework:** Laravel (PHP)
- **Database:** PostgreSQL / MySQL
- **Authentication:** Laravel Sanctum (JWT/Token-based)
- **API Documentation:** Postman Collection

---

## 🏗 Core Folder Structure
For newcomers, here are the most important directories you need to know:
- `routes/api.php`: Where all API endpoints are defined. Every request from the Frontend comes through here first.
- `app/Http/Controllers/`: Contains the business logic for handling incoming requests.
  - *Examples:* `AuthController` (Login/Register), `EventController` (Fetching events), `OrganizerEventController` (Event management for organizers), etc.
- `app/Models/`: Contains the classes that interact directly with the database tables (Eloquent ORM).
- `database/migrations/`: Files defining the database schema. When running migrations, Laravel uses these to create tables.

---

## 🔐 Authentication Flow
The project uses **Laravel Sanctum** for issuing tokens.
1. A user sends a Login Request.
2. The Backend validates credentials and returns a `Bearer Token`.
3. The Frontend stores this token and attaches it to the `Authorization: Bearer <token>` header of all subsequent requests to identify the user.

---

## 🛠 Setup & Installation Guide

1. **Install Dependencies:**
   ```bash
   composer install
   npm install
   ```

2. **Environment Configuration:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   *Note: Open the `.env` file, find the `DB_` variables, and fill in your MySQL/PostgreSQL database credentials (Database Name, User, Password).*

3. **Run Migrations & Seeders (Create tables & mock data):**
   ```bash
   php artisan migrate
   # If the project has Seeders, you can optionally run: php artisan db:seed
   ```

4. **Start the Server:**
   ```bash
   composer run dev 
   # This command simultaneously runs 'php artisan serve' and 'npm run dev'
   ```
   The server will default to `http://127.0.0.1:8000`.

---

## 🚀 Useful Terminal Commands (Cheat Sheet)
- `php artisan route:list`: View all available API endpoints.
- `php artisan make:controller NameController`: Generate a new Controller.
- `php artisan make:model Name -m`: Generate a Model along with its Migration file.
- `php artisan tinker`: Open the console to test database queries directly.

---

## 📖 API Endpoints Documentation

The complete and interactive API documentation is provided as a Postman Collection.
You can find it in this repository at: `postman/DANAEventpark.postman_collection.json`.

### Quick Reference of Key Endpoints (Base URL: `/api`)

**1. Authentication**
- `POST /auth/register` - Register a new account
- `POST /auth/login` - Login and receive Bearer Token
- `POST /auth/logout` - Logout (Requires Token)
- `GET /auth/me` - Get current user profile (Requires Token)

**2. Public Events**
- `GET /events` - List all published events (Search & Filter)
- `GET /events/{id}` - View event details
- `GET /categories` - List all event categories

**3. Attendee Actions (Requires Token)**
- `POST /events/{id}/register` - Register for an event (Waitlist automatically handled)
- `POST /events/{id}/cancel` - Cancel a registration
- `POST /events/{id}/reviews` - Submit a review
- `GET /attendee/dashboard/registrations` - View all registered events

**4. Organizer Actions (Requires Token + Organizer Role)**
- `GET /organizer/dashboard/stats` - View organizer statistics
- `POST /organizer/events` - Create a new event
- `PUT /organizer/events/{id}` - Update event details
- `PUT /organizer/events/{id}/status` - Publish or cancel an event
