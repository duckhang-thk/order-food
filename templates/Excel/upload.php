<?= $this->Form->create(null, [
    'type' => 'file',
]) ?>

<?= $this->Form->control('excel_file', [
    'type' => 'file',
    'label' => 'Excelファイル',
    'accept' => '.xlsx,.xls',
]) ?>

<?= $this->Form->button('アップロード') ?>

<?= $this->Form->end() ?>