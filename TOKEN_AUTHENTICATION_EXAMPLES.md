# Token Authentication Methods for Course Categories API

## 🔐 **Different Ways to Use Tokens**

### **1. Authorization Header (Standard)**
```bash
curl -X GET "http://127.0.0.1:8000/api/v1/course-categories" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

### **2. Query Parameter (URL)**
```bash
curl -X GET "http://127.0.0.1:8000/api/v1/course-categories-flexible?token=YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

### **3. Custom Header**
```bash
curl -X GET "http://127.0.0.1:8000/api/v1/course-categories-flexible" \
  -H "X-API-Token: YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

### **4. Form Data (POST)**
```bash
curl -X POST "http://127.0.0.1:8000/api/v1/course-categories-flexible" \
  -H "Accept: application/json" \
  -d "token=YOUR_TOKEN_HERE&per_page=10&page=1"
```

### **5. JSON Body**
```bash
curl -X POST "http://127.0.0.1:8000/api/v1/course-categories-flexible" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "token": "YOUR_TOKEN_HERE",
    "per_page": 10,
    "page": 1
  }'
```

## 🌐 **Browser Testing**

### **Query Parameter Method:**
```
http://127.0.0.1:8000/api/v1/course-categories-flexible?token=YOUR_TOKEN_HERE
```

### **With Pagination:**
```
http://127.0.0.1:8000/api/v1/course-categories-flexible?token=YOUR_TOKEN_HERE&per_page=5&page=2
```

## 📱 **JavaScript Examples**

### **Query Parameter:**
```javascript
const token = 'YOUR_TOKEN_HERE';
const url = `http://127.0.0.1:8000/api/v1/course-categories-flexible?token=${token}&per_page=10&page=1`;

fetch(url, {
    method: 'GET',
    headers: {
        'Accept': 'application/json'
    }
})
.then(response => response.json())
.then(data => console.log(data));
```

### **Custom Header:**
```javascript
const token = 'YOUR_TOKEN_HERE';

fetch('http://127.0.0.1:8000/api/v1/course-categories-flexible', {
    method: 'GET',
    headers: {
        'X-API-Token': token,
        'Accept': 'application/json'
    }
})
.then(response => response.json())
.then(data => console.log(data));
```

### **JSON Body:**
```javascript
const token = 'YOUR_TOKEN_HERE';

fetch('http://127.0.0.1:8000/api/v1/course-categories-flexible', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    },
    body: JSON.stringify({
        token: token,
        per_page: 10,
        page: 1
    })
})
.then(response => response.json())
.then(data => console.log(data));
```

## 🔧 **PHP Examples**

### **Query Parameter:**
```php
<?php
$token = 'YOUR_TOKEN_HERE';
$url = "http://127.0.0.1:8000/api/v1/course-categories-flexible?token=$token&per_page=10&page=1";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);

$response = curl_exec($ch);
$data = json_decode($response, true);
curl_close($ch);

echo "Categories: " . json_encode($data['data']['data']) . "\n";
?>
```

### **Custom Header:**
```php
<?php
$token = 'YOUR_TOKEN_HERE';
$url = 'http://127.0.0.1:8000/api/v1/course-categories-flexible';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'X-API-Token: ' . $token,
    'Accept: application/json'
]);

$response = curl_exec($ch);
$data = json_decode($response, true);
curl_close($ch);

echo "Categories: " . json_encode($data['data']['data']) . "\n";
?>
```

## 📊 **Postman Examples**

### **Query Parameter:**
1. Method: `GET`
2. URL: `http://127.0.0.1:8000/api/v1/course-categories-flexible`
3. Params:
   - `token`: `YOUR_TOKEN_HERE`
   - `per_page`: `10`
   - `page`: `1`

### **Custom Header:**
1. Method: `GET`
2. URL: `http://127.0.0.1:8000/api/v1/course-categories-flexible`
3. Headers:
   - `X-API-Token`: `YOUR_TOKEN_HERE`
   - `Accept`: `application/json`

### **JSON Body:**
1. Method: `POST`
2. URL: `http://127.0.0.1:8000/api/v1/course-categories-flexible`
3. Headers:
   - `Content-Type`: `application/json`
   - `Accept`: `application/json`
4. Body (raw JSON):
   ```json
   {
     "token": "YOUR_TOKEN_HERE",
     "per_page": 10,
     "page": 1
   }
   ```

## 🎯 **Which Method to Use?**

- **Authorization Header**: Most secure, standard REST API practice
- **Query Parameter**: Easiest for browser testing and simple integrations
- **Custom Header**: Good for custom integrations
- **Form Data**: Useful for form submissions
- **JSON Body**: Best for complex data with authentication

## ⚠️ **Security Notes**

- **Query Parameters**: Tokens appear in URLs and logs (less secure)
- **Headers**: More secure, not visible in URLs
- **JSON Body**: Secure but requires POST requests

Choose the method that best fits your use case!
