package aiagent.dacn.agentforedu.config;

import aiagent.dacn.agentforedu.entity.Role;
import aiagent.dacn.agentforedu.entity.User;
import aiagent.dacn.agentforedu.repository.UserRepository;
import lombok.RequiredArgsConstructor;
import lombok.extern.slf4j.Slf4j;
import org.springframework.boot.CommandLineRunner;
import org.springframework.security.crypto.password.PasswordEncoder;
import org.springframework.stereotype.Component;

@Component
@RequiredArgsConstructor
@Slf4j
public class AdminInitializer implements CommandLineRunner {

    private final UserRepository userRepository;
    private final PasswordEncoder passwordEncoder;

    @Override
    public void run(String... args) {
        // Kiểm tra xem admin đã tồn tại chưa
        if (!userRepository.existsByUsername("admin")) {
            User admin = new User();
            admin.setUsername("admin");
            admin.setPassword(passwordEncoder.encode("admin123"));
            admin.setEmail("admin@agentforedu.com");
            admin.setFullName("Administrator");
            admin.setRole(Role.ADMIN);
            
            userRepository.save(admin);
            log.info("✅ Admin account created: username=admin, password=admin123");
        } else {
            // Nếu admin đã tồn tại, cập nhật role thành ADMIN (phòng trường hợp role bị sai)
            userRepository.findByUsername("admin").ifPresent(admin -> {
                if (admin.getRole() != Role.ADMIN) {
                    admin.setRole(Role.ADMIN);
                    userRepository.save(admin);
                    log.info("✅ Updated admin role to ADMIN");
                } else {
                    log.info("✅ Admin account already exists with ADMIN role");
                }
            });
        }
    }
}
