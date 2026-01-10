package aiagent.dacn.agentforedu.controller;

import aiagent.dacn.agentforedu.dto.UserResponse;
import aiagent.dacn.agentforedu.entity.Role;
import aiagent.dacn.agentforedu.entity.User;
import aiagent.dacn.agentforedu.repository.UserRepository;
import aiagent.dacn.agentforedu.service.AdminStatsService;
import aiagent.dacn.agentforedu.service.UserService;
import io.swagger.v3.oas.annotations.Operation;
import io.swagger.v3.oas.annotations.security.SecurityRequirement;
import io.swagger.v3.oas.annotations.tags.Tag;
import lombok.RequiredArgsConstructor;
import org.springframework.data.domain.Page;
import org.springframework.data.domain.PageRequest;
import org.springframework.data.domain.Pageable;
import org.springframework.data.domain.Sort;
import org.springframework.http.ResponseEntity;
import org.springframework.security.access.prepost.PreAuthorize;
import org.springframework.web.bind.annotation.*;

import java.util.HashMap;
import java.util.List;
import java.util.Map;
import java.util.stream.Collectors;

@RestController
@RequestMapping("/api/admin")
@RequiredArgsConstructor
@PreAuthorize("hasRole('ADMIN')")
@Tag(name = "Admin", description = "API quản trị hệ thống (ADMIN ONLY)")
@SecurityRequirement(name = "bearerAuth")
public class AdminController {
    
    private final UserService userService;
    private final UserRepository userRepository;
    private final AdminStatsService adminStatsService;
    
    // ==================== DASHBOARD STATS ====================
    
    @GetMapping("/stats")
    @Operation(summary = "Lấy thống kê tổng quan dashboard")
    public ResponseEntity<Map<String, Object>> getDashboardStats() {
        return ResponseEntity.ok(adminStatsService.getDashboardStats());
    }
    
    @GetMapping("/stats/users-by-role")
    @Operation(summary = "Thống kê users theo role")
    public ResponseEntity<Map<String, Long>> getUsersByRole() {
        return ResponseEntity.ok(adminStatsService.getUsersByRole());
    }
    
    @GetMapping("/stats/activity")
    @Operation(summary = "Thống kê hoạt động gần đây")
    public ResponseEntity<Map<String, Object>> getRecentActivity() {
        return ResponseEntity.ok(adminStatsService.getRecentActivity());
    }
    
    // ==================== USER MANAGEMENT ====================
    
    @GetMapping("/users")
    @Operation(summary = "Lấy danh sách users với pagination")
    public ResponseEntity<Map<String, Object>> getAllUsers(
            @RequestParam(defaultValue = "0") int page,
            @RequestParam(defaultValue = "10") int size,
            @RequestParam(defaultValue = "createdAt") String sortBy,
            @RequestParam(defaultValue = "desc") String sortDir
    ) {
        Sort sort = sortDir.equalsIgnoreCase("asc") 
            ? Sort.by(sortBy).ascending() 
            : Sort.by(sortBy).descending();
        Pageable pageable = PageRequest.of(page, size, sort);
        
        Page<User> userPage = userRepository.findAll(pageable);
        
        List<UserResponse> users = userPage.getContent().stream()
            .map(this::toUserResponse)
            .collect(Collectors.toList());
        
        Map<String, Object> response = new HashMap<>();
        response.put("users", users);
        response.put("currentPage", userPage.getNumber());
        response.put("totalItems", userPage.getTotalElements());
        response.put("totalPages", userPage.getTotalPages());
        
        return ResponseEntity.ok(response);
    }
    
    @GetMapping("/users/search")
    @Operation(summary = "Tìm kiếm users")
    public ResponseEntity<Map<String, Object>> searchUsers(
            @RequestParam String keyword,
            @RequestParam(defaultValue = "0") int page,
            @RequestParam(defaultValue = "10") int size
    ) {
        Pageable pageable = PageRequest.of(page, size, Sort.by("createdAt").descending());
        Page<User> userPage = userRepository.searchUsers(keyword, pageable);
        
        List<UserResponse> users = userPage.getContent().stream()
            .map(this::toUserResponse)
            .collect(Collectors.toList());
        
        Map<String, Object> response = new HashMap<>();
        response.put("users", users);
        response.put("currentPage", userPage.getNumber());
        response.put("totalItems", userPage.getTotalElements());
        response.put("totalPages", userPage.getTotalPages());
        
        return ResponseEntity.ok(response);
    }
    
    @GetMapping("/users/filter")
    @Operation(summary = "Lọc users theo role")
    public ResponseEntity<Map<String, Object>> filterUsersByRole(
            @RequestParam String role,
            @RequestParam(defaultValue = "0") int page,
            @RequestParam(defaultValue = "10") int size
    ) {
        Pageable pageable = PageRequest.of(page, size, Sort.by("createdAt").descending());
        Role roleEnum = Role.valueOf(role.toUpperCase());
        Page<User> userPage = userRepository.findByRole(roleEnum, pageable);
        
        List<UserResponse> users = userPage.getContent().stream()
            .map(this::toUserResponse)
            .collect(Collectors.toList());
        
        Map<String, Object> response = new HashMap<>();
        response.put("users", users);
        response.put("currentPage", userPage.getNumber());
        response.put("totalItems", userPage.getTotalElements());
        response.put("totalPages", userPage.getTotalPages());
        
        return ResponseEntity.ok(response);
    }
    
    @GetMapping("/users/{id}")
    @Operation(summary = "Lấy thông tin user theo ID")
    public ResponseEntity<UserResponse> getUserById(@PathVariable Long id) {
        return ResponseEntity.ok(userService.getUserById(id));
    }
    
    @PutMapping("/users/{id}/role")
    @Operation(summary = "Đổi role của user")
    public ResponseEntity<Map<String, Object>> changeUserRole(
            @PathVariable Long id,
            @RequestBody Map<String, String> request
    ) {
        User user = userRepository.findById(id)
            .orElseThrow(() -> new RuntimeException("User không tồn tại"));
        
        String newRole = request.get("role");
        user.setRole(Role.valueOf(newRole.toUpperCase()));
        userRepository.save(user);
        
        Map<String, Object> response = new HashMap<>();
        response.put("message", "Đổi role thành công");
        response.put("user", toUserResponse(user));
        
        return ResponseEntity.ok(response);
    }
    
    @DeleteMapping("/users/{id}")
    @Operation(summary = "Xóa user")
    public ResponseEntity<Map<String, String>> deleteUser(@PathVariable Long id) {
        userService.deleteUser(id);
        return ResponseEntity.ok(Map.of("message", "Xóa người dùng thành công"));
    }
    
    @GetMapping("/users/recent")
    @Operation(summary = "Lấy 10 users đăng ký gần nhất")
    public ResponseEntity<List<UserResponse>> getRecentUsers() {
        List<UserResponse> users = userRepository.findTop10ByOrderByCreatedAtDesc()
            .stream()
            .map(this::toUserResponse)
            .collect(Collectors.toList());
        return ResponseEntity.ok(users);
    }
    
    // ==================== HELPER ====================
    
    private UserResponse toUserResponse(User user) {
        UserResponse response = new UserResponse();
        response.setId(user.getId());
        response.setUsername(user.getUsername());
        response.setEmail(user.getEmail());
        response.setFullName(user.getFullName());
        response.setAvatarUrl(user.getAvatarUrl());
        response.setRole(user.getRole());
        response.setCreatedAt(user.getCreatedAt());
        response.setUpdatedAt(user.getUpdatedAt());
        return response;
    }
}
