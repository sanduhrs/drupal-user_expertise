<?php

require_once './includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

function user_expertise_generate($user, $content_type = 'node') {
  if ($content_type == 'node') {
    $result = db_query("SELECT tn.tid, SUM(vv.value) as expertise, COUNT(vv.content_id) as affirmations FROM {votingapi_vote} vv
                          INNER JOIN {term_node} tn ON tn.nid=vv.content_id
                          WHERE vv.uid=%d AND vv.content_type='%s' GROUP BY tn.tid", $user->uid, $content_type);
  }
  else {
    $result = db_query("SELECT tn.tid, SUM(vv.value) as expertise, COUNT(vv.vote_id) as affirmations FROM {votingapi_vote} vv
                          INNER JOIN {comments} c
                          INNER JOIN {term_node} tn
                          WHERE vv.uid=%d AND vv.content_type='%s' AND vv.content_id=c.cid AND tn.nid=c.nid GROUP BY tn.tid", $user->uid, $content_type);
  }
  db_query("DELETE FROM {user_expertise} WHERE uid=%d", $user->uid);

  while ($obj = db_fetch_object($result)) {
//     print_r($obj);
    db_query("INSERT INTO {user_expertise} (tid, uid, expertise, affirmations, content_type, generated) VALUES (%d, %d, %f, %d, '%s', %d)", $obj->tid, $user->uid, $obj->expertise, $obj->affirmations, $content_type, time());
  }
}

$result = db_query("SELECT DISTINCT(uid) FROM {votingapi_vote} ORDER BY uid ASC");
while ($account = db_fetch_object($result)) {
//   print_r($account);
  set_time_limit(30);
  user_expertise_generate($account);
  user_expertise_generate($account, 'comment');
  echo 'Timelimit: '. ini_get('max_execution_time') ."</br>";
  echo 'uid: '. $account->uid .' Updated.'."</br>";
}

print 'done.';