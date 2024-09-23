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
    cookieUpdate();

    /*console.log("Búsqueda event 1");
    console.log({
      "busqueda": jQuery(".quicksearch").val(),
      'ubicacion': jQuery('#select-ubicacion').val(),
      'tipo': jQuery('select[name=tipo]').val(),
    });*/
    gtag('event', 'busqueda_ofertas', {
      "busqueda": jQuery(".quicksearch").val(),
      'ubicacion': jQuery('#select-ubicacion').val(),
      'tipo': jQuery('select[name=tipo]').val(),
    });

    gtag('event', 'PruebClick', {});
    timeout = setTimeout( delayed, threshold );
  };
}

document.querySelectorAll(".filters-button-group input[type='radio'], .filters-button-group input[type='checkbox'],select").forEach((element) => {
  element.addEventListener('change',function(){

    cookieUpdate();
    /*console.log("Búsqueda event 2");
    console.log({
      "busqueda": jQuery(".quicksearch").val(),
      'ubicacion': jQuery('#select-ubicacion').val(),
      'tipo': jQuery('select[name=tipo]').val(),
    });*/

    gtag('event', 'busqueda_ofertas', {
      "busqueda": jQuery(".quicksearch").val(),
      'ubicacion': jQuery('#select-ubicacion').val(),
      'tipo': jQuery('select[name=tipo]').val(),
    });

    jQuery("#noresults").removeClass("show");
    selectedRadios = [];
    document.querySelectorAll(".filters-button-group input[type='radio']:checked,select").forEach((element) => {
      if(element.value!= '') selectedRadios.push(element.value);
    });
    selectedCheckboxes = [];
    document.querySelectorAll(".filters-button-group input[type='checkbox']:checked").forEach((element) => {
      if(element.value!= '') selectedCheckboxes.push(element.value);
    });
    iso.isotope();
  });
});

jQuery(".button-group *:is(input[type=text],select)").on("change", function() {
  if(jQuery(this).val() != '') jQuery(this).addClass("selected");
  else jQuery(this).removeClass("selected");
});
jQuery(".button-group *:is(input[type=checkbox])").on("change", function() {
  if(jQuery(this).prop("checked")) jQuery(this).addClass("selected");
  else jQuery(this).removeClass("selected");
});

jQuery( document ).ready(function() {
  selectedRadios = [];
  document.querySelectorAll(".filters-button-group input[type='radio']:checked,select").forEach((element) => {
    if(element.value!= '') selectedRadios.push(element.value);
  });
  selectedCheckboxes = [];
  document.querySelectorAll(".filters-button-group input[type='checkbox']:checked").forEach((element) => {
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


  var totalfiltereds = jQuery('.jobs-grid').data('isotope').filteredItems.length;

  jQuery("#numberresults > b").text(totalfiltereds);

  if(totalfiltereds == 0) {
    jQuery("#noresults").addClass("show");
    if(jQuery(".quicksearch").val() != '') jQuery("#noresults p:first-of-type span").text(' "'+jQuery(".quicksearch").val()+'"')
  } else {
    jQuery("#noresults").removeClass("show");
    jQuery("#noresults p:first-of-type span").text("")
  }

}
iso.on( 'layoutComplete', layoutComplete );

function cookieUpdate() {
  var checks = [];
  jQuery("#city-group-ubicacion input[type='checkbox']:checked").each(function() {
    checks.push(jQuery(this).val());
    
  });
  //console.log(checks);
  var filters = {
    'ubicacion': jQuery('#select-ubicacion').val(),
    'localizacion': checks,
    'tipo': jQuery('select[name=tipo]').val(),
    'search': jQuery('input.quicksearch').val(),
  };
  jQuery.cookie('buscaroferta', JSON.stringify(filters));
}
