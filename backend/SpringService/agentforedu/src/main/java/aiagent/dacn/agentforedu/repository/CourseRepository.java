package aiagent.dacn.agentforedu.repository;

import aiagent.dacn.agentforedu.entity.Course;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.stereotype.Repository;

import java.time.LocalDateTime;
import java.util.List;

@Repository
public interface CourseRepository extends JpaRepository<Course, Long> {
    List<Course> findByCreatedBy(Long createdBy);
    
    // Admin stats
    long countByCreatedAtAfter(LocalDateTime date);
    
    List<Course> findTop10ByOrderByCreatedAtDesc();
}
