# Admins, Roles & Permissions API Documentation

This document describes the API endpoints for authenticating admins, managing roles, managing permissions, and performing CRUD operations on admin accounts.

## Overview

- **Auth Endpoints Base URL:** `/api/admins` (No authentication required)
- **CRUD Endpoints Base URL:** `/api` (Requires Admin Authentication)
- **Authentication Header:** `Authorization: Bearer {token}` (where token is the base64 token returned from the login response)

---

## Auth Endpoints

### 1. Admin Login

Authenticate an admin with their phone and password. Returns admin details including their access token, role status, and the dynamically compiled permissions list from their assigned role.

- **Method:** `POST`
- **Endpoint:** `/api/admins/login`
- **Request Headers:**
  - `Accept: application/json`
  - `Content-Type: application/json`
- **Request Body:**
  ```json
  {
      "phone": "0501234567",
      "password": "securepassword123"
  }
  ```
- **Success Response (200 OK):**
  ```json
  {
      "statusCode": 200,
      "status": "success",
      "message": {
          "id": 1,
          "name": "Super Admin",
          "email": "admin@nartaqi.com",
          "phone": "0501234567",
          "token": "eyJ0b2tlbiI6ImFkYTQ5...",
          "role": "superadmin",
          "role_id": 23,
          "permissions": [
              {
                  "name": "إدارة المستخدمين",
                  "description": "القدرة على إدارة المستخدمين",
                  "role_id": 23,
                  "permission_id": "4"
              },
              {
                  "name": "عرض المستخدمين",
                  "description": "القدرة على عرض قائمة المستخدمين",
                  "role_id": 23,
                  "permission_id": "5"
              }
          ],
          "created_at": "2026-06-09T09:00:00.000000Z",
          "updated_at": "2026-06-09T09:05:00.000000Z"
      }
  }
  ```
- **Error Response (401 Unauthorized):**
  ```json
  {
      "statusCode": 401,
      "status": "failed",
      "message": "بيانات الدخول غير صحيحة"
  }
  ```

---

## Admins CRUD Endpoints (Protected)

> [!IMPORTANT]
> All endpoints below require the `Authorization` header with a valid admin token.

### 2. Get All Admins List

Retrieve a list of all admins with their assigned roles.

- **Method:** `GET`
- **Endpoint:** `/api/admins/list`
- **Success Response (200 OK):**
  ```json
  {
      "statusCode": 200,
      "status": "success",
      "message": [
          {
              "id": 1,
              "name": "Super Admin",
              "email": "admin@nartaqi.com",
              "phone": "0501234567",
              "token": "eyJ0b2tlbiI6ImFkYTQ5...",
              "role": "superadmin",
              "role_id": 23,
              "permissions": [
                  {
                      "name": "إدارة المستخدمين",
                      "description": "القدرة على إدارة المستخدمين",
                      "role_id": 23,
                      "permission_id": "4"
                  }
              ],
              "created_at": "2026-06-09T09:00:00.000000Z",
              "updated_at": "2026-06-09T09:05:00.000000Z",
              "role_relation": {
                  "id": 23,
                  "name": "مدير النظام",
                  "description": "صلاحيات كاملة للنظام",
                  "permissions_ids": [4]
              }
          }
      ]
  }
  ```

---

### 3. Create Admin

Create a new admin and assign a `role_id` to them.

- **Method:** `POST`
- **Endpoint:** `/api/admins/store`
- **Request Body (JSON):**
  ```json
  {
      "name": "Employee Admin",
      "email": "employee@nartaqi.com",
      "phone": "0509876543",
      "password": "password123",
      "password_confirmation": "password123",
      "role": "employee",
      "role_id": 23
  }
  ```

---

### 4. Show Admin

Retrieve details of a specific admin by their ID.

- **Method:** `POST`
- **Endpoint:** `/api/admins/show`
- **Request Body (JSON):**
  ```json
  {
      "id": 2
  }
  ```

---

### 5. Update Admin

Update an existing admin. The password and `role_id` are optional.

- **Method:** `POST`
- **Endpoint:** `/api/admins/update`
- **Request Body (JSON):**
  ```json
  {
      "id": 2,
      "name": "Updated Employee Name",
      "email": "employee@nartaqi.com",
      "phone": "0509876543",
      "role": "employee",
      "role_id": 24
  }
  ```

---

### 6. Delete Admin

Remove an admin from the system.

- **Method:** `POST`
- **Endpoint:** `/api/admins/delete`
- **Request Body (JSON):**
  ```json
  {
      "id": 2
  }
  ```

---

### 7. Direct Update Password

Change the password of any admin directly.

- **Method:** `POST`
- **Endpoint:** `/api/admins/update_password`
- **Request Body (JSON):**
  ```json
  {
      "id": 2,
      "password": "newsecurepassword123",
      "password_confirmation": "newsecurepassword123"
  }
  ```

---

## Roles CRUD Endpoints (Protected)

### 8. Get All Roles List

- **Method:** `GET`
- **Endpoint:** `/api/roles/list`
- **Success Response (200 OK):**
  ```json
  {
      "statusCode": 200,
      "status": "success",
      "message": [
          {
              "id": 23,
              "name": "مدير النظام",
              "description": "صلاحيات كاملة للنظام",
              "permissions_ids": [4, 5, 6],
              "created_at": "2026-06-09T09:00:00.000000Z",
              "updated_at": "2026-06-09T09:00:00.000000Z"
          }
      ]
  }
  ```

---

### 9. Create Role

Create a new role with description and assigned permission IDs.

- **Method:** `POST`
- **Endpoint:** `/api/roles/store`
- **Request Body (JSON):**
  ```json
  {
      "name": "مدير المحتوى",
      "description": "صلاحية إدارة المقالات والشارات",
      "permissions_ids": [4, 5]
  }
  ```

---

### 10. Show Role

- **Method:** `POST`
- **Endpoint:** `/api/roles/show`
- **Request Body (JSON):**
  ```json
  {
      "id": 23
  }
  ```

---

### 11. Update Role

- **Method:** `POST`
- **Endpoint:** `/api/roles/update`
- **Request Body (JSON):**
  ```json
  {
      "id": 23,
      "name": "مدير النظام المطور",
      "description": "صلاحيات كاملة للنظام معدلة",
      "permissions_ids": [4, 5, 6, 7]
  }
  ```

---

### 12. Delete Role

- **Method:** `POST`
- **Endpoint:** `/api/roles/delete`
- **Request Body (JSON):**
  ```json
  {
      "id": 23
  }
  ```

---

## Permissions CRUD Endpoints (Protected)

### 13. Get All Permissions List

- **Method:** `GET`
- **Endpoint:** `/api/permissions/list`
- **Success Response (200 OK):**
  ```json
  {
      "statusCode": 200,
      "status": "success",
      "message": [
          {
              "id": 4,
              "name": "إدارة المستخدمين",
              "description": "القدرة على إدارة المستخدمين",
              "created_at": "2026-06-09T09:00:00.000000Z",
              "updated_at": "2026-06-09T09:00:00.000000Z"
          }
      ]
  }
  ```

---

### 14. Create Permission

- **Method:** `POST`
- **Endpoint:** `/api/permissions/store`
- **Request Body (JSON):**
  ```json
  {
      "name": "إضافة مستخدم",
      "description": "القدرة على إضافة مستخدم جديد"
  }
  ```

---

### 15. Show Permission

- **Method:** `POST`
- **Endpoint:** `/api/permissions/show`
- **Request Body (JSON):**
  ```json
  {
      "id": 4
  }
  ```

---

### 16. Update Permission

- **Method:** `POST`
- **Endpoint:** `/api/permissions/update`
- **Request Body (JSON):**
  ```json
  {
      "id": 4,
      "name": "تعديل مستخدم مطور",
      "description": "تعديل المستخدمين"
  }
  ```

---

### 17. Delete Permission

- **Method:** `POST`
- **Endpoint:** `/api/permissions/delete`
- **Request Body (JSON):**
  ```json
  {
      "id": 4
  }
  ```
