package standard

import (
	"errors"

	"github.com/MagicalTux/gophp/core"
)

// output buffering functions

//> func bool ob_start ([ callable $output_callback = NULL [, int $chunk_size = 0 [, int $flags = PHP_OUTPUT_HANDLER_STDFLAGS ]]] )
func fncObStart(ctx core.Context, args []*core.ZVal) (*core.ZVal, error) {
	var outputCallback *core.Callable
	var chunkSize *core.ZInt
	var flags *core.ZInt
	_, err := core.Expand(ctx, args, &outputCallback, &chunkSize, &flags)
	if err != nil {
		return nil, err
	}

	if ctx.GetConfig("ob_in_handler", core.ZBool(false).ZVal()).AsBool(ctx) {
		return nil, errors.New("ob_start(): Cannot use output buffering in output buffering display handlers")
	}

	b := ctx.Global().AppendBuffer()

	if outputCallback != nil {
		b.CB = *outputCallback
	}

	if chunkSize != nil {
		b.ChunkSize = int(*chunkSize)
	}

	// TODO flags

	return core.ZBool(true).ZVal(), nil
}

//> func void ob_flush ( void )
func fncObFlush(ctx core.Context, args []*core.ZVal) (*core.ZVal, error) {
	buf := ctx.Global().Buffer()
	if buf != nil {
		return core.ZNULL, buf.Flush()
	}
	return core.ZNULL, nil
}

//> func void ob_clean ( void )
func fncObClean(ctx core.Context, args []*core.ZVal) (*core.ZVal, error) {
	buf := ctx.Global().Buffer()
	if buf == nil {
		return core.ZNULL, nil
	}

	buf.Clean()
	return core.ZNULL, nil
}

//> func bool ob_end_clean ( void )
func fncObEndClean(ctx core.Context, args []*core.ZVal) (*core.ZVal, error) {
	buf := ctx.Global().Buffer()
	if buf == nil {
		return core.ZBool(false).ZVal(), nil
	}

	buf.Clean()
	return core.ZBool(true).ZVal(), buf.Close()
}

//> func bool ob_end_flush ( void )
func fncObEndFlush(ctx core.Context, args []*core.ZVal) (*core.ZVal, error) {
	buf := ctx.Global().Buffer()
	if buf == nil {
		return core.ZBool(false).ZVal(), nil
	}

	return core.ZBool(true).ZVal(), buf.Close()
}