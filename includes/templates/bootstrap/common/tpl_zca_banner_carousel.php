<?php
/**
 * ZCA Banners Carousel
 * Plugin Template
 * 
 * BOOTSTRAP v3.0.0
 *
 */

$content = '';
$banner_cnt = 0;

$new_banner_search = $find_banners;
$my_banner_filter='';
// secure pages
if ($request_type === 'SSL') {
    $my_banner_filter=" AND banners_on_ssl=1";
}
$sql = "select banners_id from " . TABLE_BANNERS . " where status = 1 " . $new_banner_search . $my_banner_filter . " order by banners_sort_order";
$banners = $db->Execute($sql);

// if no active banner in the specified banner group then the box will not show
if ($banners->EOF) {
  return;
}
?>

<div id="carouselGroup<?php echo (int)$banner_group; ?>" class="carousel slide" data-ride="carousel">
    <ol class="carousel-indicators">
       <li data-target="#carouselGroup<?php echo (int)$banner_group; ?>" data-slide-to="0" class="active"></li>
        <li data-target="#carouselGroup<?php echo (int)$banner_group; ?>" data-slide-to="1"></li>
        <li data-target="#carouselGroup<?php echo (int)$banner_group; ?>" data-slide-to="2"></li>
    </ol>
    <div class="carousel-inner rounded">
<?php 
    foreach ($banners as $row) {
        $banner_cnt++;
    
        $addBannerClass = '';   
        if ($banner_cnt === 1) {
            $addBannerClass = 'active';    
        }
?>
        <div class="carousel-item <?php echo $addBannerClass; ?>">
            <?php echo zen_display_banner('static', $row['banners_id']); ?>
        </div>
  
<?php } ?>

    </div>
    <a class="carousel-control-prev" href="#carouselGroup<?php echo (int)$banner_group; ?>" role="button" data-slide="prev">
      <span class="carousel-control-prev-icon" aria-hidden="true"></span>
      <span class="sr-only">Previous</span>
    </a>
    <a class="carousel-control-next" href="#carouselGroup<?php echo (int)$banner_group; ?>" role="button" data-slide="next">
      <span class="carousel-control-next-icon" aria-hidden="true"></span>
      <span class="sr-only">Next</span>
    </a>
</div>
