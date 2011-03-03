<?php

	class SNS_Comment_Notifier extends Plugin {
		
		public function action_comment_insert_after ( $comment ) {
			
			// get our options
			$iam_key = Options::get( 'sns-comment-notifier__iam_key' );
			$iam_secret = Options::get( 'sns-comment-notifier__iam_secret' );
			$topic_arn = Options::get( 'sns-comment-notifier__topic_arn' );
			
			// if any of them are null, just return
			if ( $iam_key == null || $iam_secret == null || $topic_arn == null ) {
				return;
			}
			
			$post = $comment->post;
			
			switch ( $comment->status ) {
				case Comment::STATUS_APPROVED:
					$status = 'Approved';
					break;
				case Comment::STATUS_SPAM:
					$status = 'SPAM';
					break;
				case Comment::STATUS_UNAPPROVED:
					$status = 'UNAPPROVED';
					break;
				default:
					$status = $comment->status;
					break;
			}
			
			$subject = _t( '[%1$s] New %2$s Comment on %3$s ', array( Options::get('title'), $status, $post->title ), 'sns-comment-notifier' );
			$message = <<<MESSAGE
The following comment was added to the post "%1\$s".
%2\$s

Author: %3\$s <%4\$s>
URL: %5\$s
IP: %6\$s ( %7\$s )

%8\$s

-----
Moderate comments: %9\$s
MESSAGE;
			
			$ip = long2ip( $comment->ip );
			$hostname = gethostbyaddr( $ip );
			
			// translate and substitute in the values
			$message = _t( $message, array( $post->title, $post->permalink, $comment->name, $comment->email, $comment->url, $ip, $hostname, $comment->content, URL::get( 'admin', 'page=comments' ) ), 'sns-comment-notifier' );
			
			// and finally hit the service
			require_once('awstools/aws.php');
			
			try {
				$sns = new SimpleNotification( $iam_key, $iam_secret );
				$sns->publish( $topic_arn, $message, $subject );
			}
			catch ( Exception $e ) {
				EventLog::log( _t( 'Unable to notify SNS of new comment.' ), 'err', 'default', null, array( $e->getMessage() ) );
			}
			
		}
		
		public function configure() {
			
			$ui = new FormUI( 'sns-comment-notifier' );
			
			$iam_key = $ui->append( 'text', 'iam_key', 'sns-comment-notifier__iam_key', _t( 'IAM Key' ) );
			$iam_secret = $ui->append( 'text', 'iam_secret', 'sns-comment-notifier__iam_secret', _t( 'IAM Secret' ) );
			$topic_arn = $ui->append( 'text', 'topic_arn', 'sns-comment-notifier__topic_arn', _t( 'Topic ARN' ) );
			
			$iam_key->add_validator( 'validate_required' );
			$iam_secret->add_validator( 'validate_required' );
			$topic_arn->add_validator( 'validate_required' );
			
			$ui->append( 'submit', 'save', _t( 'Save' ) );
			$ui->out();
			
		}
		
	}

?>