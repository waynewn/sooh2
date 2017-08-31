<?php
namespace Sooh2\HTML\Form;

/**
 * Description of FormEdit
 *
 * @author simon.wang
 */
class Edit extends \Sooh2\HTML\Form\Base{
    protected function btnSubmit()
    {
        return '<input type=submit value="修改">';
    }
}
