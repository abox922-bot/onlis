<?php
  $result[] = ['task_id' => $task_id, 'acquiring' => $dd, 'fiscal' => $head_dd]
  $head_dd = ['type' => $kktFnc, 'taxationType' => 'patent', 'electronically' => false, 'ignoreNonFiscalPrintErrors' => true, 'operator' => $operator_dd,	'items' => $items, 'payments' => $payments];
  $head_dd = ['type' => 'sell',	'taxationType' => 'patent',	'electronically' => true, 'clientInfo' => $client_dd,	'operator' => $operator_dd,	'items' => $items, 'payments' => $payments];
  if($ordTp == 5)	{
    $sbFnc = 6000;
    $result = array();
    $dd = ['func' => $sbFnc];
    $dd1 = ['task_id' => $task_id, 'acquiring_rep' => $dd];
    $result[] = $dd1;
  }

  if($ordTp == 6)	{
    $kktFnc = "closeShift";
    $result = array();
    //$dd = array('func' => $sbFnc);
    $head_dd = ['type' => $kktFnc, 'operator' => $operator_dd];
    $dd1 = ['task_id' => $task_id, 'fiscal' => $head_dd];
    $result[] = $dd1;
  }
  if($ordTp == 4)	{
    $sbFnc = 4002;
    $kktFnc = "sellReturn";
  }

  $client_dd = ['emailOrPhone' => $phone];
  $operator_dd = ['name' => $operator_name];
  $tax_dd = ['type' => 'none'];
  $sbFnc = 4000;
  $kktFnc = "sell";
  //==============================================================================
  "fiscal":{"errorCode":2,"errorDescription":"Нет связи"}
  "fiscal":{"fiscalParams":{"fiscalDocumentDateTime":"2025-10-15T07:37:00+05:00","fiscalDocumentNumber":126496,"fiscalDocumentSign":"2421904225","fiscalReceiptNumber":6,"fnNumber":"7280440500108519","fnsUrl":"nalog.gov.ru","registrationNumber":"0006140113055383","shiftNumber":770,"total":379}
  "acquiring":{"error":"При выполнении функции NFun (4000) : Ошибка 4451"}
  "acquiring":{"AmountClear":"111400","Amount":"111400","CardName":"MIR Credit","CardType":"10","TrxDate":"15.10.2025","TrxTime":"08:36:24","TermNum":"11308475","MerchNum":"160000014138","AuthCode":"838756","RRN":"528898178117","MerchantTSN":"20","MerchantBatchNum":"0","ClientCard":"************1706","ClientExpiryDate":"3306","Cheque":"         ONL.IS COFFEE          \r\n  г.Лесной, Свердловская обл.   \r\n        ул.Ленина, д.80         \r\n         т.79222986775          \r\n15.10.25     08:36    ЧЕК   0020\r\nПАО СБЕРБАНК              Оплата\r\nТ: 11308475       М:160000014138\r\nMIR               A0000006581010\r\nКарта:(E)       ************1706\r\nСумма (Руб):             1114.00\r\nКомиссия за операцию - 0 Руб.\r\n            ОДОБРЕНО\r\nК/А: 838756  RRN:   528898178117\r\n Подпись клиента не требуется  \r\n2E7415FAEF2B830C215C913F608132EF\r\n================================\r\n\r\n\r\n\r\n~S"}
