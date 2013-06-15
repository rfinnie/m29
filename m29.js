if(typeof XMLHttpRequest == 'undefined') {
  XMLHttpRequest = function () {
    try { return new ActiveXObject('Msxml2.XMLHTTP.6.0'); }
      catch (e) {}
    try { return new ActiveXObject('Msxml2.XMLHTTP.3.0'); }
      catch (e) {}
    try { return new ActiveXObject('Microsoft.XMLHTTP'); }
      catch (e) {}
    //Microsoft.XMLHTTP points to Msxml2.XMLHTTP and is redundant
    throw new Error('This browser does not support XMLHttpRequest.');
  };
}

var Base64URL = {
  stringify: function (wordArray) {
    var out = CryptoJS.enc.Base64.stringify(wordArray);
    out = out.replace(/\+/g, '-');
    out = out.replace(/\//g, '_');
    out = out.replace(/=/g, '');
    return(out);
  },
  parse: function (instr) {
    var out = CryptoJS.enc.Base64.parse(instr);
    out = out.replace(/-/g, '+');
    out = out.replace(/_/g, '/');
    var cipherParams = CryptoJS.lib.CipherParams.create({
      ciphertext: out
    });
    return cipherParams;
  }
}

function prepareSubmitForm(form, htmlresponse) {
  if(!form.longUrl.className.match(/(?:^|\s)jssecure(?!\S)/)) {
    form.longUrl.className += ' jssecure';
  }
}

function submitEncryptedUrl(form, htmlresponse) {
  // The XMLHttpRequest method is for the benefit of the front page only.
  // Please do not use this by third-party scripts.  A fully-featured JSON
  // API is available; please see the main web site for details.

  var longUrl = form.longUrl.value;
  if(longUrl == '') { return false; }

  // Remove the original URL in case XHR fails, to avoid leaking the
  // unencrypted URL to M29.
  form.longUrl.value = '';
  // Disable the submit button during processing.
  form.submitButton.disabled = true;

  // Encrypt the URL.
  var key = CryptoJS.lib.WordArray.random(128/8);
  var key1 = CryptoJS.lib.WordArray.create(key.words.slice(0,2));
  var key2 = CryptoJS.lib.WordArray.create(key.words.slice(2,4));
  var encrypted = CryptoJS.AES.encrypt(longUrl, key, { mode: CryptoJS.mode.ECB, padding: CryptoJS.pad.ZeroPadding });

  var longUrlEncrypted = encrypted.ciphertext.toString(Base64URL);
  var firstKey = key1.toString(Base64URL);
  var secondKey = key2.toString(Base64URL);

  // Submit longUrlEncrypted and firstKey.
  xhr = new XMLHttpRequest();
  xhr.open('POST', window.location, false);
  xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
  xhr.send('xhrRequest=true&longUrlEncrypted=' + longUrlEncrypted + '&firstKey=' + firstKey);

  // Parse the results.
  xml = xhr.responseXML;
  if(xml.getElementsByTagName('shortUrl')[0]) {
    short_url = xml.getElementsByTagName('shortUrl')[0].firstChild.nodeValue;
    if(xml.getElementsByTagName('shortUrlIncomplete')[0]) {
      if(xml.getElementsByTagName('shortUrlIncomplete')[0].firstChild.nodeValue == 'true') {
        short_url = short_url + '/' + secondKey;
      }
    }
    htmlresponse.innerHTML = '<p class="center">The following short URL has been created:</p><p class="center"><a href="' + short_url + '" rel="nofollow">' + short_url + '</a></p>';
  } else if(xml.getElementsByTagName('error')[0]) {
    errorval = xml.getElementsByTagName('error')[0].firstChild.nodeValue;
    htmlresponse.innerHTML = '<p class="center"><strong>Error:</strong> ' + errorval + '</p>';
  } else {
    htmlresponse.innerHTML = '<p class="center"><strong>An unknown error has occurred.</strong></p>';
  } 

  // If all went well, re-enable the button.
  form.submitButton.disabled = false;

  return false;
}
