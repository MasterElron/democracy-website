<?php
require_once dirname(__FILE__).'/sql/autoload.inc';
// \SYSTEM\autoload::registerFolder(dirname(__FILE__).'/sql','SQL');
\SYSTEM\autoload::registerFolder(dirname(__FILE__),'SAI');
\SYSTEM\SAI\sai::register('\SAI\saimod_beta');
\SYSTEM\autoload::registerFolder(dirname(__FILE__).'/cannon');