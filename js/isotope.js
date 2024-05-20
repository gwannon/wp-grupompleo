var qsRegex;
var selectedRadios = [];
var selectedCheckboxes = [];

var iso = jQuery('.jobs-grid').isotope({
  // options
  itemSelector: '.jobs-item',
  layoutMode: 'fitRows',
  filter: function() {
    if(qsRegex) {
      if((jQuery(this).text()+jQuery(this).data("search")).match( qsRegex )) {
        if(selectedRadios.length === 0 && selectedCheckboxes.length === 0) return true;
        else {
          var control = 0;  
          selectedRadios.forEach((element) => {
            if (jQuery(this).hasClass(element)) control++;
          });
  
          var control2 = 0;
          if(selectedCheckboxes.length > 0) {
            selectedCheckboxes.forEach((element) => {
              if (jQuery(this).hasClass(element)) control2++;
            });
          } else control2 = 1;
  
          if(control == selectedRadios.length && control2 > 0) return true;
          else return false;
        }
      } else {
        return false;
      }
    } else {
      if(selectedRadios.length === 0 && selectedCheckboxes.length === 0) return true;
      else {
        var control = 0;  
        selectedRadios.forEach((element) => {
          if (jQuery(this).hasClass(element)) control++;
        });

        var control2 = 0;
        if(selectedCheckboxes.length > 0) {
          selectedCheckboxes.forEach((element) => {
            if (jQuery(this).hasClass(element)) control2++;
          });
        } else control2 = 1;

        if(control == selectedRadios.length && control2 > 0) return true;
        else return false;
      }
      return true;
    } 
  },
});

// use value of search field to filter
var quicksearch = document.querySelector('.quicksearch');
quicksearch.addEventListener( 'keyup', debounce( function() {
  //console.log(quicksearch.value.toLowerCase().normalize("NFD").replace(/\p{Diacritic}/gu, ""));
  qsRegex = new RegExp( quicksearch.value.toLowerCase().normalize("NFD").replace(/\p{Diacritic}/gu, ""), 'gi' );
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

document.querySelectorAll("input[type='radio'],input[type='checkbox'],select").forEach((element) => {
  element.addEventListener('change',function(){
    jQuery("#noresults").removeClass("show");
    selectedRadios = [];
    document.querySelectorAll("input[type='radio']:checked,select").forEach((element) => {
      if(element.value!= '') selectedRadios.push(element.value);
    });
    selectedCheckboxes = [];
    document.querySelectorAll("input[type='checkbox']:checked").forEach((element) => {
      if(element.value!= '') selectedCheckboxes.push(element.value);
    });
    iso.isotope();
  });
});

jQuery( document ).ready(function() {
  selectedRadios = [];
  document.querySelectorAll("input[type='radio']:checked,select").forEach((element) => {
    if(element.value!= '') selectedRadios.push(element.value);
  });
  selectedCheckboxes = [];
  document.querySelectorAll("input[type='checkbox']:checked").forEach((element) => {
    if(element.value!= '') selectedCheckboxes.push(element.value);
  });
  iso.isotope();
});


jQuery(window).on("resize", function(event){
  iso.isotope();
});


function layoutComplete() {
  var minheight = 0;
  jQuery('.jobs-item').not('.jobs-item:hidden').each(function() {
    if(jQuery(this).outerHeight() > minheight) {
      minheight = jQuery(this).outerHeight();
    }
  });
  jQuery('.jobs-item').css("min-height", minheight + "px");
  console-log("min-height: "+ minheight + "px");

  if(jQuery('.jobs-grid').data('isotope').filteredItems.length == 0) {
    console.log("NO hay resultados");
    jQuery("#noresults").addClass("show");
  } else {
    jQuery("#noresults").removeClass("show");
  }

}
iso.on( 'layoutComplete', layoutComplete );
