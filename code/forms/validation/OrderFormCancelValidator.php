<?php


class OrderFormCancelValidator extends RequiredFields
{
    public function php($data)
    {
        $this->form->saveDataToSession();

        return parent::php($data);
    }
}