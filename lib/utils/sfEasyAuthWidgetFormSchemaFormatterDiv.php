<?php
// only declare this class if the user hasn't created it already
class sfEasyAuthWidgetFormSchemaFormatterDiv extends sfWidgetFormSchemaFormatter 
{
  protected
    $rowFormat = '%label%%error%%field%<br />%help%',
    $helpFormat = '<span class="help">%help%</span><br />',
    $errorRowFormat = '<div>%errors%</div>',
    $errorListFormatInARow = '%errors%',
    $errorRowFormatInARow = '<div class="formError">%error%</div>',
    $namedErrorRowFormatInARow = '%name%: %error%<br />',
    $decoratorFormat = '<div id="formContainer">%content%</div>';
}
