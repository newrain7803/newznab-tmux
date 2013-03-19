# Paths

export TMUX_POWERLINE_DIR_LIB="$TMUX_POWERLINE_DIR_HOME/lib"
export TMUX_POWERLINE_DIR_SEGMENTS="$TMUX_POWERLINE_DIR_HOME/segments"
export TMUX_POWERLINE_DIR_TEMPORARY="/tmp/tmux-powerline_${USER}"
export TMUX_POWERLINE_DIR_THEMES="$TMUX_POWERLINE_DIR_HOME/themes"
export TMUX_POWERLINE_RCFILE="$HOME/.tmux-powerlinerc"
export TMUX_POWERLINE_RCFILE_DEFAULT="$HOME/.tmux-powerlinerc.default"

if [ ! -d "$TMUX_POWERLINE_DIR_TEMPORARY" ]; then
	mkdir "$TMUX_POWERLINE_DIR_TEMPORARY"
fi
