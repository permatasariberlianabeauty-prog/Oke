<?php
/**
 * NOXARA - Admin Panel Footer
 */
?>
</div><!-- /.admin-content -->
</div><!-- /.admin-main -->
</div><!-- /.admin-layout -->

<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<script>
// Admin sidebar toggle
document.addEventListener('DOMContentLoaded', function(){
  const toggle = document.getElementById('adminSidebarToggle');
  const sidebar = document.getElementById('adminSidebar');
  if(toggle && sidebar){
    toggle.addEventListener('click', function(){
      sidebar.classList.toggle('open');
    });
  }
  // Active nav
  const items = document.querySelectorAll('.admin-nav-item');
  items.forEach(item => {
    if(item.href && window.location.pathname.includes(item.getAttribute('href').split('/').pop().replace('.php',''))){
      item.classList.add('active');
    }
  });
});
</script>
</body>
</html>
