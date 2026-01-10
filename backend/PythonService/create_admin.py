"""
Script tạo hoặc update tài khoản Admin
Chạy: python create_admin.py
"""
import requests
import sys

SPRING_BOOT_URL = "http://localhost:8080"

def register_admin():
    """Đăng ký tài khoản admin mới"""
    print("=" * 50)
    print("TẠO TÀI KHOẢN ADMIN")
    print("=" * 50)
    
    data = {
        "username": "admin",
        "password": "admin123",
        "email": "admin@agentforedu.com",
        "fullName": "System Administrator"
    }
    
    try:
        response = requests.post(
            f"{SPRING_BOOT_URL}/api/auth/register",
            json=data,
            timeout=10
        )
        
        if response.status_code == 200:
            print("✅ Đăng ký thành công!")
            print(f"   Username: admin")
            print(f"   Password: admin123")
            print("\n⚠️  Bây giờ cần UPDATE role thành ADMIN trong database:")
            print("   UPDATE users SET role = 'ADMIN' WHERE username = 'admin';")
            return True
        else:
            print(f"❌ Lỗi: {response.text}")
            if "đã tồn tại" in response.text.lower() or "exists" in response.text.lower():
                print("\n💡 Tài khoản 'admin' đã tồn tại.")
                print("   Chạy SQL để update role:")
                print("   UPDATE users SET role = 'ADMIN' WHERE username = 'admin';")
            return False
    except requests.exceptions.ConnectionError:
        print("❌ Không thể kết nối đến Spring Boot!")
        print("   Hãy chắc chắn Spring Boot đang chạy trên port 8080")
        return False
    except Exception as e:
        print(f"❌ Lỗi: {e}")
        return False

def test_login():
    """Test đăng nhập với tài khoản admin"""
    print("\n" + "=" * 50)
    print("TEST ĐĂNG NHẬP ADMIN")
    print("=" * 50)
    
    data = {
        "username": "admin",
        "password": "admin123"
    }
    
    try:
        response = requests.post(
            f"{SPRING_BOOT_URL}/api/auth/login",
            json=data,
            timeout=10
        )
        
        if response.status_code == 200:
            result = response.json()
            user = result.get('user', {})
            print("✅ Đăng nhập thành công!")
            print(f"   Username: {user.get('username')}")
            print(f"   Role: {user.get('role')}")
            print(f"   Email: {user.get('email')}")
            
            if user.get('role') == 'ADMIN':
                print("\n🎉 Tài khoản đã có quyền ADMIN!")
            else:
                print(f"\n⚠️  Role hiện tại: {user.get('role')}")
                print("   Cần UPDATE role thành ADMIN:")
                print("   UPDATE users SET role = 'ADMIN' WHERE username = 'admin';")
            return True
        else:
            print(f"❌ Đăng nhập thất bại: {response.text}")
            return False
    except requests.exceptions.ConnectionError:
        print("❌ Không thể kết nối đến Spring Boot!")
        return False
    except Exception as e:
        print(f"❌ Lỗi: {e}")
        return False

if __name__ == "__main__":
    print("\n🔐 ADMIN ACCOUNT SETUP\n")
    
    # Thử đăng ký trước
    register_admin()
    
    # Test đăng nhập
    test_login()
    
    print("\n" + "=" * 50)
    print("HƯỚNG DẪN:")
    print("=" * 50)
    print("""
1. Nếu chưa có tài khoản admin:
   - Chạy SQL: INSERT INTO users (username, password, email, role, full_name, created_at, updated_at)
               VALUES ('admin', '$2a$10$N9qo8uLOickgx2ZMRZoMyeIjZRGdjGj/n3.rsS8gPBpKahNLa.1o2', 
                       'admin@agentforedu.com', 'ADMIN', 'System Administrator', NOW(), NOW());

2. Nếu đã có tài khoản nhưng chưa phải ADMIN:
   - Chạy SQL: UPDATE users SET role = 'ADMIN' WHERE username = 'admin';

3. Hoặc update tài khoản của bạn thành ADMIN:
   - Chạy SQL: UPDATE users SET role = 'ADMIN' WHERE username = 'YOUR_USERNAME';

4. Đăng nhập với:
   - Username: admin (hoặc username của bạn)
   - Password: admin123 (hoặc password của bạn)
""")
