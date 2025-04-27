# Avoid adding duplicate lines or lines that begin with spaces to the history
HISTCONTROL=ignoreboth

# Add to the history file instead of replacing it.
shopt -s histappend

# After each command, check the window size and update the values of LINES and COLUMNS if needed
shopt -s checkwinsize

# colored prompt;
force_color_prompt=yes
color_prompt=yes

export PS1='\[\e[1;32m\]php@app:\[\e[1;34m\]\w\[\e[0m\]$ '

if [ -f ~/.bash_aliases ]; then
    . ~/.bash_aliases
fi
