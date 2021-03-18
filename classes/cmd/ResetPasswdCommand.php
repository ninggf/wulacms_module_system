<?php

namespace system\classes\cmd;

use system\classes\model\UserTable;
use wulaphp\artisan\ArtisanCommand;
use wulaphp\auth\Passport;

class ResetPasswdCommand extends ArtisanCommand {
    public function cmd() {
        return 'reset:passwd';
    }

    public function desc() {
        return 'reset user password';
    }

    public function argDesc() {
        return '<user>';
    }

    public function paramValid($options) {
        global $argc;
        if ($argc < 4) {
            $this->help("give me a user to reset his/her password!");
        } else if ($argc > 4) {
            $this->help("can not reset more than one user's password!");
        }

        return true;
    }

    protected function execute($options) {
        $user           = new UserTable();
        $pwd            = rand_str(10);
        $data['passwd'] = Passport::passwd($pwd);
        //TODO：不同租户的用户的name表示
        $where = ['name' => $this->opt()];
        if (!$user->exist($where)) {
            $this->error('user "' . $where['name'] . '" does not exist!');
            exit(1);
        }
        $user->update($data, $where);
        $this->success($this->opt() . '\'s password has been changed to "' . $pwd . '"');
    }
}