<?php

/**
 * @package All-in-One-SEO-Pack
 */

/**
 * Class aioseop_logging
 *
 * Class used for logging.
 */
// @codingStandardsIgnoreStart
class aioseop_logging {
// @codingStandardsIgnoreEnd

	const LOG_LENGTH = 100;
	const LOG_EXPIRY_MINS = 5;
	static $__log = array();
	static $log_types = array( 'error', 'warn', 'info', 'debug' );

	private $log_length = self::LOG_LENGTH;
	private $log_expiry = self::LOG_EXPIRY_MINS;

	function __construct() {
		$this->log_length = apply_filters( 'aioseop_log_length', self::LOG_LENGTH );
		$this->log_expiry = apply_filters( 'aioseop_log_expiry_minutes', self::LOG_EXPIRY_MINS );

		// use the action to extend behavior.
		add_action( 'aiosp_log', array( $this, 'log' ), 10, 5 );
		// update db only once.
		add_action( 'shutdown', array( $this, 'update' ) );
	}

	function update() {
		$__log = self::$__log;
		if ( count( $__log ) > $this->log_length ) {
			$__log = array_slice( $__log, 0 - $this->log_length );
		}

		set_transient( 'aiosp_event_log', $__log, $this->log_expiry * MINUTE_IN_SECONDS );
	}

	// log to the database and into file.
	function log( $log_msg, $log_type = 'debug', $file = null, $line = null, $force = false ) {
		global $aioseop_options;

		// first check if logging is enabled?
		if ( ! ( ! empty( $aioseop_options ) && isset( $aioseop_options['aiosp_do_log'] ) && $aioseop_options['aiosp_do_log'] ) ) {
			if ( ! ( $force || defined( 'AIOSEOP_DO_LOG' ) ) ) {
				return;
			}
		}

		if ( ! in_array( $log_type, self::$log_types, true ) ) {
			return;
		}

		$time = date( 'F j, Y H:i:s', current_time( 'timestamp', true ) );

		if ( is_array( $log_msg ) || is_object( $log_msg ) ) {
			$log_msg = print_r( $log_msg, true );
		}

		self::$__log[] = array(
			'type' => $log_type,
			'msg'  => $log_msg,
			'time' => $time,
			'file' => $file,
			'line' => $line,
		);

		$log_line = sprintf( '%s(%s) - %s:%d - %s', $time, $log_type, $file, $line, $log_msg );
		error_log( $log_line );
	}

	public static function show() {
		$logs = get_transient( 'aiosp_event_log' );
		?>
<div id="aiosp-logs">
	<div id="aiosp-log-actions">
		<input type="radio" name="aiosp-log-type" value="all" id="aiosp-log-all"><label for="aiosp-log-all" aria-label="all"><?php _e( 'All', 'all-in-one-seo-pack' ); ?></label>
		<?php foreach ( self::$log_types as $type ) { ?>
		<input type="radio" name="aiosp-log-type" value="<?php echo $type; ?>" id="aiosp-log-<?php echo $type; ?>" <?php echo $type === 'info' ? 'checked' : ''; ?>><label for="aiosp-log-<?php echo $type; ?>" aria-label="<?php echo $type; ?>"><?php echo ucwords( $type ); ?></label>
		<?php } ?>
	</div>
	<div id="aiosp-log-console">
		<?php
		if ( $logs ) {
			foreach ( $logs as $log ) {
				$style = 'info' !== $log['type'] ? 'display:none' : '';
				?>
		<div class="aiosp-log aiosp-log-<?php echo $log['type']; ?>" style="<?php echo $style; ?>">
			<span class="aiosp-log-timestamp"><?php echo $log['time']; ?></span>
			<span class="aiosp-log-type"><?php echo ucwords( $log['type'] ); ?></span>
			<span class="aiosp-log-msg"><?php echo basename( $log['file'] ); ?>:<?php echo $log['line']; ?> - <?php echo esc_html( $log['msg'] ); ?></span>
		</div>
				<?php
			}
		} else {
			_e( 'No logs found', 'all-in-one-seo-pack' );
		}
		?>
	</div>
</div>
		<?php
	}
}

new aioseop_logging();
