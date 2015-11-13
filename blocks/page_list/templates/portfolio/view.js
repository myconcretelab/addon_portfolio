$(document).ready(function(){
    var galleries = $('.portfolio-gallery');
    var $win = $(window);
    galleries.each(function(i){
      var
      container = $(this),
      $imgs = container.find("img"),
      masonryOptions = {columnWidth: '.masonry-item'},
      bID = container.data('bid'),
      // quick search regex
      qsRegex = false;
      // If a div.gutter-sizer is present, we add it to the option, otherwise the plugin doesn't work
      if (container.find(".gutter-sizer").size()) masonryOptions.gutter = '.gutter-sizer';

      container.imagesLoaded(function(){
        $isotope = container.isotope({ masonry: masonryOptions,
    						itemSelector: '.masonry-item'
    						}
        );
  	    $isotope.isotope('on', 'layoutComplete', function (items) {
  	        loadVisible($imgs, 'lazylazy');
  	    });

  	    $win.on('scroll', function () {
  	        loadVisible($imgs, 'lazylazy');
  	    });

  	    $imgs.lazyload({
  	        effect: "fadeIn",
  	        failure_limit: Math.max($imgs.length - 1, 0),
  	        event: 'lazylazy'
  	    });
        $isotope.isotope('layout');

        $('#filter-set-' + bID).on('click', 'a', function(e) {
          e.preventDefault();
          var filterValue = $(this).attr('data-filter');
          container.isotope({ filter: filterValue })
        });
    }).always( function( instance ) {
    console.log('all images loaded');
  });
  })
    function loadVisible($els, trigger) {
        $els.filter(function () {
            var rect = this.getBoundingClientRect();
            return rect.top >= 0 && rect.top <= window.innerHeight;
        }).trigger(trigger);
    }

  // use value of search field to filter
    var $quicksearch = $('#quicksearch').keyup(debounce(searchFilter));

    function searchFilter() {
        qsRegex = new RegExp($quicksearch.val(), 'gi');
        container.isotope({
            filter: function () {
                return qsRegex ? $(this).text().match(qsRegex) : true;
            }
        });
    }

    // debounce so filtering doesn't happen every millisecond
    function debounce( fn, threshold ) {
      var timeout;
      return function debounced() {
        if ( timeout ) {
          clearTimeout( timeout );
        }
        function delayed() {
          fn();
          timeout = null;
        }
        timeout = setTimeout( delayed, threshold || 100 );
      }
    }
});
