# Niirrty.Forms.Security

Some HTML->PHP form securing methods.

3 methods for 3 differnt checks

## DynamicFormField

This class define all data of an dynamic form field with an random generated name.

A hidden form field must be defined as an part of the form that should be secured, with an random generated
form field name. The required  information about the dynamic form field name is transmitted by the session.

### Usage-Example

```php
<?php
use \Niirrty\Forms\Security\DynamicFormField;

$dynamicField = new DynamicFormField();

if ( $dynamicField->isValidRequest() )
{
   // The field is permitted successful => handle the real form data
}
else
{
   // No Request => show the form with the required form field
   // Example of output the required hidden form field:
   echo $dynamicField->buildHiddenFieldHtml();
}
```

## FormTimer

This class allow you to define an time span of an valid web form request. It means you can define how long an
really user should need minimally, to fill out the form. The maximum request time is not restricted by this class
because its not important for doing the required job

Please do not think its an summary for filling all required form fields. That's a fallacy! An form can also been
re-shown, for change some missed or wrong form field value or check an required checkbox. at least with all
required interaction 1.5 - 2 seconds. Not more! But it does the required job because bots send really fast. They
visit (scan) if they are "large" a lot of million pages in 24h. Time is money :-( so one second is an more
realistic time span for bots. So we are served well, with 1.5 seconds min request time.

Here an short usage example for preferred method with storing the request microtime inside the session:

```php
<?php

use \Niirrty\Forms\Security\FormTimer;

// Init the form timer
$formTimer = new FormTimer(
   true,                     // Use session?
   'FormTimer.NameOfMyForm'  // Session var name
);

// $isPostRequest is an imaginary value that must be replaced bei you're code to check if an POST request of
// you're form exists
if ( $isPostRequest )
{
   if ( ! $formTimer->isValidRequest() )
   {
      // no valid Timer request => Ignore this request and show the form
   }
   else
   {
      // Handle the valid request
   }
}
```

## HoneyPot

This class allows you to easy secure you're web form by an `honeypot`.

A honeypot should do the same job like in real life. He is expected to lure something.

In this case the honeypot should attract the bots. They see this field with an popular name like 'text' and will
fill it with the content of which he thinks that he would be the right.

The idea behind this field is: An bot can normally not distinguish between visible and invisible form fields if
hidden by some CSS code. If so, the bot have no idea about the current visibility state and will fill it.

The filling with something will be the identifier, that no human has send the last request, because the required
form field value is an empty string.

### Why an textarea form element is used?

Modern web browser supports the "auto fill" feature. If the browser thinks he known what content is to prefer for
an text input form field, maybe he does it also. That will generate a false-positive state "There must be an bot"
Textarea fields normal will not be auto filled by browsers.

### Short example

```php
<?php

use \Niirrty\Forms\Security\HoneyPot;

$honeypot = new HoneyPot( 'fieldName', \INPUT_POST );

if ( $honeypot->isValidRequest() )
{
   // The honeypot field is permitted successful => handle the real form data
}
else
{
   // No Request => show the form with the required form field
   // First you need to write you're CSS code. Its only an example. Please use template engines!
   echo '&lt;style type="text/css"&gt;'
       , $honeypot->buildCSS( 'inv1s1ble' )
       , "&lt;/style&gt;\n";
   // Now output the label with an message for clients that do not support CSS. e.g. "do not fill this field"
   echo '&lt;label class="inv1s1ble"&glt;&amp;#80;l&amp;#101;a&amp;#115;e&amp;#32;d&amp;#111; &amp;#110;o&amp;#116; &amp;#102;i&amp;#108;l&amp;#32;t&amp;#104;i&amp;#115; &amp;#102;i&amp;#101;l&amp;#100;&lt;/label&gt;';
   // Example of output the required textarea form field:
   echo $honeypot->buildFormField( 'inv1s1ble' );
   // ...
}
```
