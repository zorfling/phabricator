<?php

final class FundInitiativeCloseController
  extends FundController {

  private $id;

  public function willProcessRequest(array $data) {
    $this->id = $data['id'];
  }

  public function processRequest() {
    $request = $this->getRequest();
    $viewer = $request->getUser();

    $initiative = id(new FundInitiativeQuery())
      ->setViewer($viewer)
      ->withIDs(array($this->id))
      ->requireCapabilities(
        array(
          PhabricatorPolicyCapability::CAN_VIEW,
          PhabricatorPolicyCapability::CAN_EDIT,
        ))
      ->executeOne();
    if (!$initiative) {
      return new Aphront404Response();
    }

    $initiative_uri = '/'.$initiative->getMonogram();

    $is_close = !$initiative->isClosed();

    if ($request->isFormPost()) {
      $type_status = FundInitiativeTransaction::TYPE_STATUS;

      if ($is_close) {
        $new_status = FundInitiative::STATUS_CLOSED;
      } else {
        $new_status = FundInitiative::STATUS_OPEN;
      }

      $xaction = id(new FundInitiativeTransaction())
        ->setTransactionType($type_status)
        ->setNewValue($new_status);

      $editor = id(new FundInitiativeEditor())
        ->setActor($viewer)
        ->setContentSourceFromRequest($request)
        ->setContinueOnMissingFields(true);

      $editor->applyTransactions($initiative, array($xaction));

      return id(new AphrontRedirectResponse())->setURI($initiative_uri);
    }

    if ($is_close) {
      $title = pht('Close Initiative?');
      $body = pht(
        'Really close this initiative? Users will no longer be able to '.
        'back it.');
      $button_text = pht('Close Initiative');
    } else {
      $title = pht('Reopen Initiative?');
      $body = pht('Really reopen this initiative?');
      $button_text = pht('Reopen Initiative');
    }

    return $this->newDialog()
      ->setTitle($title)
      ->appendParagraph($body)
      ->addCancelButton($initiative_uri)
      ->addSubmitButton($button_text);
  }

}
