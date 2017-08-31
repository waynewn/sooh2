<?php
namespace Sooh2\HTML\Form;

/**
 * Description of FormSearch
 *
 * @author simon.wang
 */
class Search extends Sooh2\HTML\Form\Base{
    protected function btnSubmit()
    {
        return '<input type=submit value="搜索">';
    }
}
