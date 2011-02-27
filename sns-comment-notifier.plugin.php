<?php

	class SNS_Comment_Notifier extends Plugin {
		
		public function action_comment_insert_after ( $comment ) {
			
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
	
	%6\$s
	
	-----
	Moderate comments: %7\$s
MESSAGE;
			
			// translate and substitute in the values
			$message = _t( $message, array( $post->title, $post->permalink, $comment->name, $comment->email, $comment->url, $comment->content, URL::get( 'admin', 'page=comments' ) ), 'sns-comment-notifier' );
			
			// and finally hit the service
			
		}
		
	}

?>