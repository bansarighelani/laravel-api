# Laravel Authentication and API Controllers

This repository contains two core Laravel controllers for user authentication and API functionalities:

1. **LoginController**
2. **ApiController**

## Overview

These controllers are designed to handle user authentication, session management, and API operations for managing resources like notes, groups, chapters, and more. Below is a detailed description of their features and functionalities.

---

## 1. LoginController

### Description
The `LoginController` is responsible for handling user authentication, including signup, login, password recovery, and account management.

### Key Features
- **SignUp**: Register new users with validations for username, email, and password.
- **Login**: Authenticate users using username or email and password.
- **Forgot Password**: Send a reset password OTP to users via email.
- **Reset Password**: Allow users to reset their password using the OTP.
- **Logout**: Log users out and invalidate their session.
- **Delete Account**: Permanently delete a user account.

### Endpoints
| HTTP Method | Endpoint           | Description                |
|-------------|--------------------|----------------------------|
| POST        | `/signup`          | Register a new user.       |
| POST        | `/login`           | Log in a user.             |
| POST        | `/forgot-password` | Send password reset OTP.   |
| POST        | `/reset-password`  | Reset user password.       |
| POST        | `/logout`          | Log out the user.          |
| DELETE      | `/delete-account`  | Delete user account.       |

---

## 2. ApiController

### Description
The `ApiController` provides functionalities to manage various resources, including notes, chapters, groups, and suggestions. It serves as a backbone for API-driven operations in the application.

### Key Features
- **Note Management**: CRUD operations for managing notes.
- **Group and Chapter Management**: Handle groups and their associated chapters.
- **Suggestions**: Manage user suggestions.
- **Favorite Notes**: Allow users to mark notes as favorites.
- **CMS Integration**: Provide APIs for CMS-related operations.

### Endpoints
| HTTP Method | Endpoint         | Description                  |
|-------------|------------------|------------------------------|
| GET         | `/notes`         | Retrieve all notes.          |
| POST        | `/notes`         | Create a new note.           |
| PUT         | `/notes/{id}`    | Update an existing note.     |
| DELETE      | `/notes/{id}`    | Delete a note.               |
| GET         | `/groups`        | Retrieve all groups.         |
| POST        | `/suggestions`   | Submit a user suggestion.    |
| GET         | `/favorites`     | Retrieve favorite notes.     |

---

## Usage

### Authentication Workflow
1. **Signup**: Create a new user account.
2. **Login**: Authenticate and retrieve an access token.
3. Use the access token for API requests requiring authentication.

### API Requests
- Use tools like [Postman](https://www.postman.com/) or [cURL](https://curl.se/) to test the endpoints.
- Include the access token in the `Authorization` header for protected routes.

---

## Code Examples

### User Registration Example
```json
POST /signup
{
  "name": "John Doe",
  "username": "johndoe",
  "email": "john.doe@example.com",
  "password": "StrongPassword123!"
}
```

### Note Creation Example
```json
POST /notes
{
  "title": "New Note",
  "content": "This is the content of the note."
}
```

---


## Contact
For questions or inquiries, please contact [bansarighelani07@gmail.com](mailto:bansarighelani07@gmail.com).
