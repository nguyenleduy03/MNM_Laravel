package aiagent.dacn.agentforedu.service;

import aiagent.dacn.agentforedu.entity.Role;
import aiagent.dacn.agentforedu.repository.*;
import lombok.RequiredArgsConstructor;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.time.LocalDateTime;
import java.util.HashMap;
import java.util.Map;

@Service
@RequiredArgsConstructor
public class AdminStatsService {

    private final UserRepository userRepository;
    private final CourseRepository courseRepository;
    private final LessonRepository lessonRepository;
    private final QuizRepository quizRepository;
    private final ChatSessionRepository chatSessionRepository;
    private final ChatMessageRepository chatMessageRepository;
    private final FlashcardDeckRepository flashcardDeckRepository;
    private final FlashcardRepository flashcardRepository;

    @Transactional(readOnly = true)
    public Map<String, Object> getDashboardStats() {
        Map<String, Object> stats = new HashMap<>();
        
        // Tổng số liệu
        stats.put("totalUsers", userRepository.count());
        stats.put("totalCourses", courseRepository.count());
        stats.put("totalLessons", lessonRepository.count());
        stats.put("totalQuizzes", quizRepository.count());
        stats.put("totalChatSessions", chatSessionRepository.count());
        stats.put("totalChatMessages", chatMessageRepository.count());
        stats.put("totalFlashcardDecks", flashcardDeckRepository.count());
        stats.put("totalFlashcards", flashcardRepository.count());
        
        return stats;
    }

    @Transactional(readOnly = true)
    public Map<String, Long> getUsersByRole() {
        Map<String, Long> roleStats = new HashMap<>();
        
        for (Role role : Role.values()) {
            long count = userRepository.countByRole(role);
            roleStats.put(role.name(), count);
        }
        
        return roleStats;
    }

    @Transactional(readOnly = true)
    public Map<String, Object> getRecentActivity() {
        Map<String, Object> activity = new HashMap<>();
        
        LocalDateTime now = LocalDateTime.now();
        LocalDateTime today = now.toLocalDate().atStartOfDay();
        LocalDateTime weekAgo = now.minusDays(7);
        LocalDateTime monthAgo = now.minusDays(30);
        
        // Users registered
        activity.put("usersToday", userRepository.countByCreatedAtAfter(today));
        activity.put("usersThisWeek", userRepository.countByCreatedAtAfter(weekAgo));
        activity.put("usersThisMonth", userRepository.countByCreatedAtAfter(monthAgo));
        
        // Courses created
        activity.put("coursesToday", courseRepository.countByCreatedAtAfter(today));
        activity.put("coursesThisWeek", courseRepository.countByCreatedAtAfter(weekAgo));
        activity.put("coursesThisMonth", courseRepository.countByCreatedAtAfter(monthAgo));
        
        // Chat messages
        activity.put("messagesThisWeek", chatMessageRepository.countByTimestampAfter(weekAgo));
        
        return activity;
    }
}
