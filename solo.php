<?php /* Template Name: CustomPage */ ?>
<html>
<head>
<!-- <script src="https://unpkg.com/isotope-layout@3/dist/isotope.pkgd.min.js"></script> -->
<script
  src="https://code.jquery.com/jquery-3.7.1.min.js"
  integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo="
  crossorigin="anonymous"></script>

<?php /*wp_head();*/ ?>
</head>
<body>
<?php
if ( have_posts() ) {
	while ( have_posts() ) {
		the_post();
		the_content();
	}
} ?>						
<?php wp_footer(); ?>
</body>
</html>
