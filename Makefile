LESS_FILES=website/css/style.less
LESS_INCLUDES=website/css/pizza.less
CSS_FILES=$(LESS_FILES:.less=.css)
LESSC_BIN=lessc -x --include-path="/"

all: less

less: $(CSS_FILES)

%.css: %.less $(LESS_INCLUDES)
	$(LESSC_BIN) $< > $@

clean:
	rm -f $(CSS_FILES)
