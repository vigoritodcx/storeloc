<?php
// Configuration split: select override environment
$config['config_split.config_split.dev']['status'] = filter_var(getenv('CONFIG_SPLIT_DEV'), FILTER_VALIDATE_BOOLEAN);
$config['config_split.config_split.test']['status'] = filter_var(getenv('CONFIG_SPLIT_TEST'), FILTER_VALIDATE_BOOLEAN);
$config['config_split.config_split.prod']['status'] = filter_var(getenv('CONFIG_SPLIT_PROD'), FILTER_VALIDATE_BOOLEAN);
