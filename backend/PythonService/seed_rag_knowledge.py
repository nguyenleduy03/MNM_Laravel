"""
Script để thêm nội dung học tập vào RAG Knowledge Base
Chạy: python seed_rag_knowledge.py
"""
import json
import os
import sys

# Fix encoding
if sys.platform == 'win32':
    import io
    sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')
    sys.stderr = io.TextIOWrapper(sys.stderr.buffer, encoding='utf-8', errors='replace')

from dotenv import load_dotenv
load_dotenv()

import google.generativeai as genai

GEMINI_API_KEY = os.getenv("GEMINI_API_KEY")
if not GEMINI_API_KEY:
    print("ERROR: GEMINI_API_KEY not found in .env")
    sys.exit(1)

genai.configure(api_key=GEMINI_API_KEY)

# ============================================================================
# NỘI DUNG HỌC TẬP - PYTHON
# ============================================================================

PYTHON_LESSONS = [
    {
        "lesson_id": 1,
        "lesson_title": "Giới Thiệu Python và Cài Đặt",
        "course_title": "Lập Trình Python",
        "content": """
Python là ngôn ngữ lập trình bậc cao, dễ học và mạnh mẽ. Được tạo ra bởi Guido van Rossum năm 1991.

Tại sao học Python?
- Cú pháp đơn giản: Dễ đọc như tiếng Anh
- Thư viện phong phú: 200,000+ packages trên PyPI
- Cộng đồng lớn: 10+ triệu developers trên thế giới
- Ứng dụng rộng rãi: Web, AI, Data Science, Automation, Game

Python trong thực tế:
- Google sử dụng Python cho Search engine, YouTube
- Instagram backend được xây dựng với Django (Python framework)
- Netflix dùng Python cho Recommendation system
- NASA sử dụng Python cho Data analysis

Cài đặt Python:
1. Truy cập python.org/downloads
2. Download Python 3.11 hoặc mới hơn
3. Khi cài đặt, nhớ check "Add Python to PATH"
4. Verify bằng lệnh: python --version

Hello World trong Python:
print("Hello, World!")
print("Chào mừng đến với Python!")

Tính toán đơn giản:
result = 10 + 20
print(f"10 + 20 = {result}")
"""
    },
    {
        "lesson_id": 2,
        "lesson_title": "Biến và Kiểu Dữ Liệu",
        "course_title": "Lập Trình Python",
        "content": """
Biến trong Python là nơi lưu trữ dữ liệu. Python không cần khai báo kiểu dữ liệu trước.

Khai báo biến:
name = "Nguyễn Văn A"
age = 20
height = 1.75
is_student = True

Các kiểu dữ liệu cơ bản trong Python:

1. Integer (int) - Số nguyên:
x = 100
y = -50
z = 0

2. Float - Số thực (số thập phân):
pi = 3.14159
temperature = 36.5
price = 99.99

3. String (str) - Chuỗi ký tự:
name = "Python"
message = 'Hello World'

4. Boolean (bool) - Giá trị logic True/False:
is_active = True
is_admin = False

Ép kiểu (Type Casting):
- String sang Int: int("123") cho kết quả 123
- Int sang String: str(20) cho kết quả "20"
- String sang Float: float("99.99") cho kết quả 99.99

F-strings để format chuỗi:
name = "An"
age = 20
print(f"Tên: {name}, Tuổi: {age}")
"""
    },
    {
        "lesson_id": 3,
        "lesson_title": "Cấu Trúc Điều Kiện If-Else",
        "course_title": "Lập Trình Python",
        "content": """
Cấu trúc điều kiện if-else dùng để thực thi code dựa trên điều kiện.

Cú pháp cơ bản:
if điều_kiện:
    # code thực thi nếu điều kiện đúng
else:
    # code thực thi nếu điều kiện sai

Ví dụ kiểm tra tuổi:
age = 18
if age >= 18:
    print("Bạn đã đủ tuổi")
else:
    print("Bạn chưa đủ tuổi")

Cấu trúc if-elif-else (nhiều điều kiện):
score = 85
if score >= 90:
    grade = "A"
elif score >= 80:
    grade = "B"
elif score >= 70:
    grade = "C"
elif score >= 60:
    grade = "D"
else:
    grade = "F"

Toán tử so sánh:
- == : bằng
- != : khác
- > : lớn hơn
- < : nhỏ hơn
- >= : lớn hơn hoặc bằng
- <= : nhỏ hơn hoặc bằng

Toán tử logic:
- and : và (cả hai điều kiện đều đúng)
- or : hoặc (ít nhất một điều kiện đúng)
- not : phủ định

Ví dụ kết hợp:
age = 25
has_license = True
if age >= 18 and has_license:
    print("Bạn được phép lái xe")
"""
    },
    {
        "lesson_id": 4,
        "lesson_title": "Vòng Lặp For và While",
        "course_title": "Lập Trình Python",
        "content": """
Vòng lặp dùng để thực thi một đoạn code nhiều lần.

Vòng lặp FOR - lặp qua một sequence:
# Lặp qua list
fruits = ["táo", "cam", "chuối"]
for fruit in fruits:
    print(fruit)

# Lặp với range
for i in range(5):
    print(i)  # In 0, 1, 2, 3, 4

# range(start, stop, step)
for i in range(1, 10, 2):
    print(i)  # In 1, 3, 5, 7, 9

Vòng lặp WHILE - lặp khi điều kiện còn đúng:
count = 0
while count < 5:
    print(count)
    count += 1

Các lệnh điều khiển vòng lặp:
- break: thoát khỏi vòng lặp ngay lập tức
- continue: bỏ qua lần lặp hiện tại, chuyển sang lần tiếp theo

Ví dụ break:
for i in range(10):
    if i == 5:
        break
    print(i)  # In 0, 1, 2, 3, 4

Ví dụ continue:
for i in range(5):
    if i == 2:
        continue
    print(i)  # In 0, 1, 3, 4 (bỏ qua 2)

Vòng lặp lồng nhau:
for i in range(3):
    for j in range(3):
        print(f"({i}, {j})")
"""
    },
    {
        "lesson_id": 5,
        "lesson_title": "Hàm (Functions)",
        "course_title": "Lập Trình Python",
        "content": """
Hàm là khối code có thể tái sử dụng, giúp code gọn gàng và dễ bảo trì.

Định nghĩa hàm với từ khóa def:
def greet():
    print("Xin chào!")

greet()  # Gọi hàm

Hàm có tham số:
def greet(name):
    print(f"Xin chào, {name}!")

greet("An")  # Xin chào, An!

Hàm có giá trị trả về (return):
def add(a, b):
    return a + b

result = add(5, 3)  # result = 8

Tham số mặc định:
def greet(name, greeting="Xin chào"):
    print(f"{greeting}, {name}!")

greet("An")  # Xin chào, An!
greet("An", "Hello")  # Hello, An!

Hàm với *args (nhiều tham số):
def sum_all(*numbers):
    total = 0
    for num in numbers:
        total += num
    return total

sum_all(1, 2, 3, 4, 5)  # 15

Hàm với **kwargs (tham số từ khóa):
def print_info(**info):
    for key, value in info.items():
        print(f"{key}: {value}")

print_info(name="An", age=20, city="HCM")

Lambda function (hàm ẩn danh):
square = lambda x: x ** 2
print(square(5))  # 25

Docstring - mô tả hàm:
def calculate_area(width, height):
    '''
    Tính diện tích hình chữ nhật.
    
    Args:
        width: Chiều rộng
        height: Chiều cao
    
    Returns:
        Diện tích = width * height
    '''
    return width * height
"""
    },
    {
        "lesson_id": 6,
        "lesson_title": "List và Tuple",
        "course_title": "Lập Trình Python",
        "content": """
List và Tuple là cấu trúc dữ liệu để lưu trữ nhiều phần tử.

LIST - Danh sách có thể thay đổi (mutable):
# Tạo list
fruits = ["táo", "cam", "chuối"]
numbers = [1, 2, 3, 4, 5]
mixed = [1, "hello", 3.14, True]

# Truy cập phần tử (index từ 0)
print(fruits[0])  # táo
print(fruits[-1])  # chuối (phần tử cuối)

# Thay đổi phần tử
fruits[0] = "nho"

# Thêm phần tử
fruits.append("dưa")  # Thêm cuối
fruits.insert(1, "xoài")  # Thêm vào vị trí 1

# Xóa phần tử
fruits.remove("cam")  # Xóa theo giá trị
del fruits[0]  # Xóa theo index
popped = fruits.pop()  # Xóa và trả về phần tử cuối

# Các phương thức list
len(fruits)  # Độ dài
fruits.sort()  # Sắp xếp
fruits.reverse()  # Đảo ngược
fruits.count("táo")  # Đếm số lần xuất hiện
fruits.index("cam")  # Tìm vị trí

# List slicing
numbers = [0, 1, 2, 3, 4, 5]
print(numbers[1:4])  # [1, 2, 3]
print(numbers[:3])   # [0, 1, 2]
print(numbers[3:])   # [3, 4, 5]

TUPLE - Bộ giá trị không thể thay đổi (immutable):
# Tạo tuple
point = (10, 20)
colors = ("red", "green", "blue")

# Truy cập phần tử
print(point[0])  # 10

# Tuple unpacking
x, y = point
print(x, y)  # 10 20

# Tuple không thể thay đổi
# point[0] = 5  # ERROR!

Khi nào dùng List vs Tuple:
- List: Khi cần thay đổi dữ liệu
- Tuple: Khi dữ liệu cố định, cần bảo vệ
"""
    },
    {
        "lesson_id": 7,
        "lesson_title": "Dictionary và Set",
        "course_title": "Lập Trình Python",
        "content": """
Dictionary và Set là cấu trúc dữ liệu quan trọng trong Python.

DICTIONARY - Từ điển (key-value pairs):
# Tạo dictionary
student = {
    "name": "Nguyễn Văn A",
    "age": 20,
    "major": "IT",
    "gpa": 3.5
}

# Truy cập giá trị
print(student["name"])  # Nguyễn Văn A
print(student.get("age"))  # 20
print(student.get("phone", "N/A"))  # N/A (default)

# Thêm/Sửa giá trị
student["email"] = "a@gmail.com"
student["age"] = 21

# Xóa
del student["gpa"]
student.pop("major")

# Các phương thức dictionary
student.keys()    # Lấy tất cả keys
student.values()  # Lấy tất cả values
student.items()   # Lấy cặp (key, value)

# Duyệt dictionary
for key, value in student.items():
    print(f"{key}: {value}")

# Dictionary comprehension
squares = {x: x**2 for x in range(5)}
# {0: 0, 1: 1, 2: 4, 3: 9, 4: 16}

SET - Tập hợp (không trùng lặp, không có thứ tự):
# Tạo set
fruits = {"táo", "cam", "chuối"}
numbers = {1, 2, 3, 3, 3}  # {1, 2, 3}

# Thêm phần tử
fruits.add("nho")

# Xóa phần tử
fruits.remove("cam")
fruits.discard("xoài")  # Không lỗi nếu không có

# Phép toán tập hợp
a = {1, 2, 3}
b = {2, 3, 4}
print(a | b)  # Union: {1, 2, 3, 4}
print(a & b)  # Intersection: {2, 3}
print(a - b)  # Difference: {1}

# Kiểm tra phần tử
print("táo" in fruits)  # True
"""
    },
    {
        "lesson_id": 8,
        "lesson_title": "Xử Lý File",
        "course_title": "Lập Trình Python",
        "content": """
Python cung cấp các hàm để đọc và ghi file.

Mở file với open():
# Các mode:
# 'r' - read (đọc, mặc định)
# 'w' - write (ghi, xóa nội dung cũ)
# 'a' - append (ghi thêm vào cuối)
# 'x' - create (tạo mới, lỗi nếu đã tồn tại)

Đọc file:
# Cách 1: Đọc toàn bộ
file = open("data.txt", "r", encoding="utf-8")
content = file.read()
file.close()

# Cách 2: Dùng with (tự động đóng file)
with open("data.txt", "r", encoding="utf-8") as file:
    content = file.read()

# Đọc từng dòng
with open("data.txt", "r") as file:
    for line in file:
        print(line.strip())

# Đọc tất cả dòng vào list
with open("data.txt", "r") as file:
    lines = file.readlines()

Ghi file:
# Ghi mới (xóa nội dung cũ)
with open("output.txt", "w", encoding="utf-8") as file:
    file.write("Dòng 1\\n")
    file.write("Dòng 2\\n")

# Ghi thêm vào cuối
with open("output.txt", "a", encoding="utf-8") as file:
    file.write("Dòng mới\\n")

# Ghi nhiều dòng
lines = ["Line 1", "Line 2", "Line 3"]
with open("output.txt", "w") as file:
    file.writelines([line + "\\n" for line in lines])

Làm việc với JSON:
import json

# Đọc JSON
with open("data.json", "r") as file:
    data = json.load(file)

# Ghi JSON
data = {"name": "An", "age": 20}
with open("data.json", "w") as file:
    json.dump(data, file, indent=2, ensure_ascii=False)

Làm việc với CSV:
import csv

# Đọc CSV
with open("data.csv", "r") as file:
    reader = csv.reader(file)
    for row in reader:
        print(row)

# Ghi CSV
with open("data.csv", "w", newline="") as file:
    writer = csv.writer(file)
    writer.writerow(["Name", "Age"])
    writer.writerow(["An", 20])
"""
    }
]

# ============================================================================
# NỘI DUNG HỌC TẬP - JAVASCRIPT
# ============================================================================

JAVASCRIPT_LESSONS = [
    {
        "lesson_id": 101,
        "lesson_title": "Giới Thiệu JavaScript",
        "course_title": "Lập Trình JavaScript",
        "content": """
JavaScript là ngôn ngữ lập trình phổ biến nhất cho web development.

JavaScript là gì?
- Ngôn ngữ lập trình kịch bản (scripting language)
- Chạy trên trình duyệt (client-side)
- Có thể chạy trên server với Node.js
- Được tạo bởi Brendan Eich năm 1995

Tại sao học JavaScript?
- Ngôn ngữ duy nhất chạy trên trình duyệt
- Full-stack development với Node.js
- Hệ sinh thái lớn: React, Vue, Angular
- Cơ hội việc làm cao

Cách chạy JavaScript:
1. Trong trình duyệt: F12 > Console
2. Trong file HTML: <script>code</script>
3. Với Node.js: node file.js

Hello World:
console.log("Hello, World!");
alert("Xin chào!");

Biến trong JavaScript:
// var - phạm vi function (cũ)
var name = "An";

// let - phạm vi block (khuyên dùng)
let age = 20;

// const - hằng số (không thể gán lại)
const PI = 3.14159;

Kiểu dữ liệu:
- String: "Hello"
- Number: 42, 3.14
- Boolean: true, false
- null: giá trị rỗng
- undefined: chưa gán giá trị
- Object: {name: "An"}
- Array: [1, 2, 3]
"""
    },
    {
        "lesson_id": 102,
        "lesson_title": "DOM Manipulation",
        "course_title": "Lập Trình JavaScript",
        "content": """
DOM (Document Object Model) cho phép JavaScript tương tác với HTML.

Truy cập phần tử:
// Theo ID
const element = document.getElementById("myId");

// Theo class
const elements = document.getElementsByClassName("myClass");

// Theo tag
const paragraphs = document.getElementsByTagName("p");

// Query selector (CSS selector)
const element = document.querySelector(".myClass");
const elements = document.querySelectorAll("p.intro");

Thay đổi nội dung:
element.innerHTML = "<b>Bold text</b>";
element.textContent = "Plain text";
element.innerText = "Visible text";

Thay đổi style:
element.style.color = "red";
element.style.backgroundColor = "blue";
element.style.fontSize = "20px";

Thay đổi class:
element.classList.add("active");
element.classList.remove("hidden");
element.classList.toggle("selected");
element.classList.contains("active");

Thay đổi attribute:
element.setAttribute("href", "https://google.com");
element.getAttribute("href");
element.removeAttribute("disabled");

Tạo và xóa phần tử:
// Tạo phần tử mới
const newDiv = document.createElement("div");
newDiv.textContent = "New element";
document.body.appendChild(newDiv);

// Xóa phần tử
element.remove();
parent.removeChild(child);

Event handling:
element.addEventListener("click", function() {
    console.log("Clicked!");
});

// Các event phổ biến:
// click, dblclick, mouseenter, mouseleave
// keydown, keyup, keypress
// submit, change, input, focus, blur
// load, scroll, resize
"""
    }
]

# ============================================================================
# NỘI DUNG HỌC TẬP - DATABASE
# ============================================================================

DATABASE_LESSONS = [
    {
        "lesson_id": 201,
        "lesson_title": "Giới Thiệu SQL và Database",
        "course_title": "Cơ Sở Dữ Liệu",
        "content": """
SQL (Structured Query Language) là ngôn ngữ để quản lý cơ sở dữ liệu quan hệ.

Database là gì?
- Nơi lưu trữ dữ liệu có tổ chức
- Cho phép truy vấn, thêm, sửa, xóa dữ liệu
- Đảm bảo tính toàn vẹn và bảo mật

Các loại Database:
1. Relational (SQL): MySQL, PostgreSQL, SQL Server
2. NoSQL: MongoDB, Redis, Cassandra
3. Graph: Neo4j
4. Time-series: InfluxDB

Các khái niệm cơ bản:
- Table (Bảng): Lưu trữ dữ liệu theo hàng và cột
- Row (Hàng): Một bản ghi dữ liệu
- Column (Cột): Một thuộc tính của dữ liệu
- Primary Key: Khóa chính, định danh duy nhất
- Foreign Key: Khóa ngoại, liên kết giữa các bảng

Các lệnh SQL cơ bản:

SELECT - Truy vấn dữ liệu:
SELECT * FROM users;
SELECT name, email FROM users WHERE age > 18;
SELECT * FROM users ORDER BY name ASC;
SELECT * FROM users LIMIT 10;

INSERT - Thêm dữ liệu:
INSERT INTO users (name, email, age) 
VALUES ('An', 'an@gmail.com', 20);

UPDATE - Cập nhật dữ liệu:
UPDATE users SET age = 21 WHERE name = 'An';

DELETE - Xóa dữ liệu:
DELETE FROM users WHERE id = 1;

CREATE TABLE - Tạo bảng:
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE,
    age INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
"""
    },
    {
        "lesson_id": 202,
        "lesson_title": "JOIN và Quan Hệ Giữa Các Bảng",
        "course_title": "Cơ Sở Dữ Liệu",
        "content": """
JOIN dùng để kết hợp dữ liệu từ nhiều bảng dựa trên quan hệ.

Các loại quan hệ:
1. One-to-One (1:1): Một user có một profile
2. One-to-Many (1:N): Một user có nhiều posts
3. Many-to-Many (N:N): Users và Courses (qua bảng trung gian)

INNER JOIN - Lấy dữ liệu khớp ở cả 2 bảng:
SELECT users.name, orders.total
FROM users
INNER JOIN orders ON users.id = orders.user_id;

LEFT JOIN - Lấy tất cả từ bảng trái, khớp từ bảng phải:
SELECT users.name, orders.total
FROM users
LEFT JOIN orders ON users.id = orders.user_id;

RIGHT JOIN - Lấy tất cả từ bảng phải, khớp từ bảng trái:
SELECT users.name, orders.total
FROM users
RIGHT JOIN orders ON users.id = orders.user_id;

FULL OUTER JOIN - Lấy tất cả từ cả 2 bảng:
SELECT users.name, orders.total
FROM users
FULL OUTER JOIN orders ON users.id = orders.user_id;

Ví dụ thực tế - Hệ thống học tập:
-- Lấy danh sách sinh viên và khóa học đã đăng ký
SELECT 
    u.name AS student_name,
    c.title AS course_title,
    e.enrolled_at
FROM users u
INNER JOIN enrollments e ON u.id = e.user_id
INNER JOIN courses c ON c.id = e.course_id
WHERE u.role = 'STUDENT';

GROUP BY và Aggregate Functions:
SELECT 
    course_id,
    COUNT(*) as student_count,
    AVG(score) as avg_score
FROM quiz_results
GROUP BY course_id
HAVING COUNT(*) > 10;

Aggregate functions:
- COUNT(): Đếm số bản ghi
- SUM(): Tính tổng
- AVG(): Tính trung bình
- MAX(): Giá trị lớn nhất
- MIN(): Giá trị nhỏ nhất
"""
    }
]

# ============================================================================
# MAIN FUNCTION
# ============================================================================

def create_embedding(text: str) -> list:
    """Tạo embedding cho text"""
    result = genai.embed_content(
        model="models/text-embedding-004",
        content=text,
        task_type="retrieval_document"
    )
    return result['embedding']

def chunk_content(content: str, chunk_size: int = 500) -> list:
    """Chia content thành chunks"""
    words = content.split()
    chunks = []
    current_chunk = []
    current_length = 0
    
    for word in words:
        current_chunk.append(word)
        current_length += len(word) + 1
        
        if current_length >= chunk_size:
            chunks.append(" ".join(current_chunk))
            current_chunk = []
            current_length = 0
    
    if current_chunk:
        chunks.append(" ".join(current_chunk))
    
    return chunks

def seed_knowledge_base():
    """Thêm nội dung học tập vào knowledge base"""
    
    # Load existing data
    knowledge_file = "knowledge_base.json"
    if os.path.exists(knowledge_file):
        with open(knowledge_file, 'r', encoding='utf-8') as f:
            documents = json.load(f)
        print(f"Loaded {len(documents)} existing documents")
    else:
        documents = []
    
    # Get existing IDs
    existing_ids = {doc['id'] for doc in documents}
    
    # Combine all lessons
    all_lessons = PYTHON_LESSONS + JAVASCRIPT_LESSONS + DATABASE_LESSONS
    
    added_count = 0
    
    for lesson in all_lessons:
        print(f"\nProcessing: {lesson['course_title']} - {lesson['lesson_title']}")
        
        # Chunk the content
        chunks = chunk_content(lesson['content'], chunk_size=600)
        
        for i, chunk in enumerate(chunks):
            doc_id = f"lesson_{lesson['lesson_id']}_chunk_{i}"
            
            # Skip if already exists
            if doc_id in existing_ids:
                print(f"  Skipping {doc_id} (already exists)")
                continue
            
            # Create embedding
            print(f"  Creating embedding for chunk {i+1}/{len(chunks)}...")
            embedding = create_embedding(chunk)
            
            # Add to documents
            documents.append({
                "id": doc_id,
                "document": chunk,
                "embedding": embedding,
                "metadata": {
                    "source": "lesson",
                    "lesson_id": lesson['lesson_id'],
                    "lesson_title": lesson['lesson_title'],
                    "course_title": lesson['course_title'],
                    "chunk_index": i,
                    "total_chunks": len(chunks)
                }
            })
            
            added_count += 1
    
    # Save to file
    with open(knowledge_file, 'w', encoding='utf-8') as f:
        json.dump(documents, f, ensure_ascii=False, indent=2)
    
    print(f"\n{'='*50}")
    print(f"DONE! Added {added_count} new documents")
    print(f"Total documents in knowledge base: {len(documents)}")
    print(f"{'='*50}")

if __name__ == "__main__":
    print("="*50)
    print("SEEDING RAG KNOWLEDGE BASE")
    print("="*50)
    seed_knowledge_base()
