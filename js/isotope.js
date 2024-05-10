var qsRegex;
var selectedRadios = [];
var iso = jQuery('.jobs-grid').isotope({
  // options
  itemSelector: '.jobs-item',
  layoutMode: 'fitRows',
  filter: function() {
    if(qsRegex) {
      if((jQuery(this).text()+jQuery(this).data("search")).match( qsRegex )) {
        if(selectedRadios.length === 0) return true;
        else {
          var control = 0;  
          selectedRadios.forEach((element) => {
            if (jQuery(this).hasClass(element)) control++;
          });
          if(control == selectedRadios.length) return true;
          else return false;
        }
      } else {
        return false;
      }
    } else {
      if(selectedRadios.length === 0) return true;
        else {
          var control = 0;  
          selectedRadios.forEach((element) => {
            if (jQuery(this).hasClass(element)) control++;
          });
          if(control == selectedRadios.length) return true;
          else return false;
        }
      return true;
    } 
  },
});

// use value of search field to filter
var quicksearch = document.querySelector('.quicksearch');
quicksearch.addEventListener( 'keyup', debounce( function() {
  qsRegex = new RegExp( quicksearch.value, 'gi' );
  iso.isotope();
}, 200 ) );

// debounce so filtering doesn't happen every millisecond
function debounce( fn, threshold ) {
  var timeout;
  threshold = threshold || 100;
  return function debounced() {
    clearTimeout( timeout );
    var args = arguments;
    var _this = this;
    function delayed() {
      fn.apply( _this, args );
    }
    timeout = setTimeout( delayed, threshold );
  };
}

document.querySelectorAll("input[type='radio'],select").forEach((element) => {
  element.addEventListener('change',function(){
    selectedRadios = [];
    document.querySelectorAll("input[type='radio']:checked,select").forEach((element) => {
      if(element.value!= '') selectedRadios.push(element.value);
    });
    iso.isotope();
  });
});