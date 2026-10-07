# AuraAuth API & Point Testing Documentation

This document covers all APIs, endpoint schemas, authentication mechanisms, and point-testing test cases (positive, negative, boundary, security) for testing with **Postman**, **cURL**, or automated CI/CD runners.

An import-ready Postman collection is also included in this repository:
👉 **[`AuraAuth_Postman_Collection.json`](file:///d:/xampp/htdocs/registration%20login%20page/AuraAuth_Postman_Collection.json)**

---

## 1. Quick Postman Import Instructions

1. Open **Postman**.
2. Click **Import** (top left).
3. Drag & drop or browse to [`AuraAuth_Postman_Collection.json`](file:///d:/xampp/htdocs/registration%20login%20page/AuraAuth_Postman_Collection.json).
4. Postman will load the entire test suite with pre-configured:
   - Environment collection variables (`{{base_url}}`, `{{session_id}}`, `{{csrf_token}}`).
   - Automated JavaScript test assertions (`pm.test()`).
   - Automatic session token capture upon login.
   - Dynamic random user generator on registration tests.

---

## 2. API Endpoints Reference

Base URL (Local XAMPP):
`http://localhost/registration%20login%20page`

| # | Endpoint | Method | Purpose | Auth Required | Expected Status Codes |
|---|----------|--------|---------|---------------|-----------------------|
| 1 | `/api/register.php` | `POST` | User registration | No | `201`, `409`, `422`, `405` |
| 2 | `/api/login.php` | `POST` | Authenticate & start session | No | `200`, `401`, `422`, `405` |
| 3 | `/api/profile.php` | `GET` | Get authenticated user info | Yes | `200`, `401`, `404` |
| 4 | `/api/profile.php` | `POST`/`PUT` | Update profile information | Yes | `200`, `401`, `409`, `422` |
| 5 | `/api/change-password.php` | `POST` | Update password | Yes | `200`, `400`, `401`, `422` |
| 6 | `/api/logout.php` | `POST`/`GET`| End session & invalidate auth | Yes | `200` |
| 7 | `/api/csrf-token.php` | `GET` | Get CSRF token & session info | No | `200` |
| 8 | `/register.php` | `POST` | Web form registration | No (CSRF required) | `302` (Redirect) |
| 9 | `/login.php` | `POST` | Web form login | No (CSRF required) | `302` (Redirect) |
| 10| `/dashboard.php` | `GET` | Web dashboard page | Yes (Session) | `200` / `302` |
| 11| `/logout.php` | `GET` | Web logout | No | `302` (Redirect) |

---

## 3. Authentication Mechanism in Postman

Authentication is supported via two interchangeable methods:
1. **Cookie Session (Automatic in Postman)**:
   When you call `/api/login.php`, PHP returns a `Set-Cookie: PHPSESSID=...` header. Postman automatically stores and passes this cookie on subsequent requests.
2. **Header Token (`X-Session-ID` or `Authorization: Bearer`)**:
   `/api/login.php` returns a `session_id` property in the JSON response.
   You can pass either:
   - `X-Session-ID: {{session_id}}`
   - `Authorization: Bearer {{session_id}}`

---

## 4. Point Testing Test Cases Matrix

### A. Positive Test Points (Happy Path)

| Point ID | Test Case | Endpoint & Method | Input Payload | Expected Output | Assertions |
|----------|-----------|-------------------|---------------|-----------------|------------|
| **TP-P01** | Register new valid user | `POST /api/register.php` | Valid `full_name`, `username`, `email`, matching `password` | Status `201 Created`, user object with ID | `pm.response.to.have.status(201)` |
| **TP-P02** | Login with valid credentials | `POST /api/login.php` | Registered `login_id` & `password` | Status `200 OK`, `session_id` returned | `pm.response.to.have.status(200)` |
| **TP-P03** | Fetch active profile | `GET /api/profile.php` | Header `X-Session-ID: {{session_id}}` | Status `200 OK`, user details | `pm.response.to.have.status(200)` |
| **TP-P04** | Update profile info | `POST /api/profile.php` | New `full_name`, `email`, `bio` | Status `200 OK`, updated data | `pm.response.to.have.status(200)` |
| **TP-P05** | Change password | `POST /api/change-password.php`| Valid `current_password`, matching `new_password` | Status `200 OK` | `pm.response.to.have.status(200)` |
| **TP-P06** | Logout | `POST /api/logout.php` | Header `X-Session-ID: {{session_id}}` | Status `200 OK`, session cleared | `pm.response.to.have.status(200)` |

---

### B. Negative Test Points

| Point ID | Test Case | Endpoint & Method | Input Payload | Expected Output | Status Code |
|----------|-----------|-------------------|---------------|-----------------|-------------|
| **TP-N01** | Duplicate username / email | `POST /api/register.php` | Username or email that already exists | `{ "status": "error", "message": "The username is already taken." }` | `409 Conflict` |
| **TP-N02** | Passwords do not match | `POST /api/register.php` | `password` != `confirm_password` | `{ "status": "error", "errors": { "confirm_password": "Passwords do not match." } }` | `422 Unprocessable` |
| **TP-N03** | Invalid email format | `POST /api/register.php` | `"email": "not-an-email"` | Field validation error | `422 Unprocessable` |
| **TP-N04** | Wrong password on login | `POST /api/login.php` | Valid username, wrong password | Generic failure message | `401 Unauthorized` |
| **TP-N05** | Non-existent user login | `POST /api/login.php` | `"login_id": "ghost_user_999"` | Generic failure message (anti-enumeration) | `401 Unauthorized` |
| **TP-N06** | Empty fields on login | `POST /api/login.php` | Empty strings `{ "login_id": "", "password": "" }` | Missing field warning | `422 Unprocessable` |
| **TP-N07** | Unauthorized profile access | `GET /api/profile.php` | Omit `X-Session-ID` / cookies | Unauthorized warning | `401 Unauthorized` |
| **TP-N08** | Wrong current password | `POST /api/change-password.php`| Invalid `current_password` | Incorrect password error | `400 Bad Request` |
| **TP-N09** | Profile access after logout | `GET /api/profile.php` | Invalidate session, reuse old token | Access denied | `401 Unauthorized` |

---

### C. Boundary & Security Test Points

| Point ID | Test Case | Target Payload | Purpose | Expected Result |
|----------|-----------|----------------|---------|-----------------|
| **TP-S01** | SQL Injection Bypass Attempt | `login_id`: `' OR '1'='1' --`<br>`password`: `' OR '1'='1'` | Test if raw SQL parameters are vulnerable | Rejected with `401 Unauthorized`. PDO prepared statements block SQL execution. |
| **TP-S02** | XSS Script Payload Injection | `username`: `<script>alert(1)</script>` | Verify regex input validation | Blocked with `422 Unprocessable Entity` (`/^[a-zA-Z0-9_]{3,30}$/`). |
| **TP-B01** | Password Length Boundary | `password`: `1234567` (7 characters, min is 8) | Check exact boundary validation | Blocked with `422 Unprocessable Entity` ("at least 8 characters"). |
| **TP-B02** | Username Length Boundary | `username`: `ab` (2 characters, min is 3) | Check exact boundary validation | Blocked with `422 Unprocessable Entity`. |

---

## 5. Ready-To-Use cURL Commands

### 1. Register User
```bash
curl -X POST "http://localhost/registration%20login%20page/api/register.php" \
  -H "Content-Type: application/json" \
  -d '{
    "full_name": "Test User",
    "username": "tester01",
    "email": "tester01@example.com",
    "password": "Password123!",
    "confirm_password": "Password123!"
  }'
```

### 2. Login User
```bash
curl -X POST "http://localhost/registration%20login%20page/api/login.php" \
  -H "Content-Type: application/json" \
  -d '{
    "login_id": "tester01",
    "password": "Password123!"
  }'
```

### 3. Get Profile (Pass Session ID)
```bash
curl -X GET "http://localhost/registration%20login%20page/api/profile.php" \
  -H "X-Session-ID: <REPLACE_WITH_SESSION_ID>"
```

### 4. Update Profile
```bash
curl -X POST "http://localhost/registration%20login%20page/api/profile.php" \
  -H "Content-Type: application/json" \
  -H "X-Session-ID: <REPLACE_WITH_SESSION_ID>" \
  -d '{
    "full_name": "Test User Updated",
    "email": "tester01_new@example.com",
    "bio": "API testing complete!"
  }'
```

### 5. Change Password
```bash
curl -X POST "http://localhost/registration%20login%20page/api/change-password.php" \
  -H "Content-Type: application/json" \
  -H "X-Session-ID: <REPLACE_WITH_SESSION_ID>" \
  -d '{
    "current_password": "Password123!",
    "new_password": "NewSecretPassword456!",
    "confirm_password": "NewSecretPassword456!"
  }'
```

### 6. Logout
```bash
curl -X POST "http://localhost/registration%20login%20page/api/logout.php" \
  -H "X-Session-ID: <REPLACE_WITH_SESSION_ID>"
```
