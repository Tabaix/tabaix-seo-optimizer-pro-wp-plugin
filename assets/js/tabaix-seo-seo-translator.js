(function () {
  function loadGoogleTranslateScript() {
    if (document.getElementById('google_translate_script')) return;

    var script = document.createElement('script');
    script.id = 'google_translate_script';
    script.src = '//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit';
    script.async = true;
    document.body.appendChild(script);
  }

  window.googleTranslateElementInit = function () {
    if (document.getElementById('google_translate_element')) {
      new google.translate.TranslateElement({ pageLanguage: 'en', autoDisplay: false }, 'google_translate_element');
    }
  };

  function triggerGoogleTranslate(langCode) {
    var selectField = document.querySelector('select.goog-te-combo');
    if (selectField) {
      selectField.value = langCode;
      selectField.dispatchEvent(new Event('change'));
    }
  }

  function getCookie(name) {
    var match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]*)'));
    if (match) return match[2];
    return null;
  }

  function setCookie(name, value, days) {
    var d = new Date();
    d.setTime(d.getTime() + (days * 24 * 60 * 60 * 1000));
    var expires = "expires=" + d.toUTCString();
    document.cookie = name + "=" + value + ";" + expires + ";path=/";
  }

  function checkBrowserLanguage(select) {
    // Check if user has already been prompted or manually selected
    if (getCookie('tabaix_seo_lang_prompted')) return;

    // Get browser languages
    var userLangs = navigator.languages || [navigator.language || navigator.userLanguage];
    if (!userLangs || userLangs.length === 0) return;

    // Find if any available translation option matches browser language (first 2 chars)
    for (var i = 0; i < userLangs.length; i++) {
      var browserLang = userLangs[i].substring(0, 2).toLowerCase();
      
      // If browser lang is English (default), we don't prompt (already on default)
      if (browserLang === 'en') {
        setCookie('tabaix_seo_lang_prompted', '1', 30);
        return;
      }

      // Look for a matching option
      var options = select.querySelectorAll('option[data-lang-code]');
      for (var j = 0; j < options.length; j++) {
        var opt = options[j];
        var optLang = opt.getAttribute('data-lang-code');

        if (optLang === browserLang && !opt.selected) {
          // Found a match! Ask user.
          var langName = opt.textContent.replace('✨', '').trim();
          var switchLang = confirm("Would you like to read this page in " + langName + "?");
          
          setCookie('tabaix_seo_lang_prompted', '1', 30);
          
          if (switchLang) {
            select.value = opt.value;
            select.dispatchEvent(new Event('change'));
          }
          return; // Stop checking after first match/prompt
        }
      }
    }
  }

  function initLanguageSwitcher() {
    var wrapper = document.querySelector('.tabaix-seo-language-switcher');
    if (!wrapper) return;

    var select = wrapper.querySelector('.tabaix-seo-language-switcher-select');
    if (!select) return;

    select.addEventListener('change', function () {
      var value = this.value;
      if (!value) return;
      
      // Remember that user chose a language, don't prompt them again
      setCookie('tabaix_seo_lang_prompted', '1', 30);
      
      if (value.startsWith('http')) {
        window.location.href = value;
      } else {
        triggerGoogleTranslate(value);
      }
    });

    loadGoogleTranslateScript();

    // Defer checking browser language slightly to let select/GT scripts load/hydrate
    setTimeout(function() {
      checkBrowserLanguage(select);
    }, 1000);
  }

  document.addEventListener('DOMContentLoaded', initLanguageSwitcher);
})();
