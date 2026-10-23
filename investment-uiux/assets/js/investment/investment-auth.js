/*! For license information please see investment-auth.js.LICENSE.txt */
"use strict";

function showError(message){
  var existing=document.querySelector('.auth-error-message'); if(existing) existing.remove();
  var d=document.createElement('div'); d.className='alert alert-danger auth-error-message mt-3'; d.style.cssText='font-size:0.875rem;margin-top:1rem;'; d.innerHTML='<i class="bi bi-exclamation-triangle me-2"></i>'+String(message).replace(/\n/g,'<br>');
  var box=document.querySelector('.login-box'); if(box) box.insertBefore(d, box.firstChild); else document.body.prepend(d);
  setTimeout(function(){ if(d.parentNode) d.parentNode.removeChild(d); },5000);
}
document.addEventListener("DOMContentLoaded",(function(){setActivelink(),fixedHeaderSpace(),featherjs(),coverimg(),dontclosedd(),checkstrength(),bstooltip(),swipernavpagination(),PageLoaderHide()}));
