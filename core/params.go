package core

import (
	"fmt"
	"reflect"
)

func (z *ZVal) Store(ctx Context, out interface{}) error {
	switch tgt := out.(type) {
	case *bool:
		s, err := z.As(ctx, ZtBool)
		if err != nil {
			return err
		}
		*tgt = bool(s.Value().(ZBool))
		return nil
	case *ZBool:
		s, err := z.As(ctx, ZtBool)
		if err != nil {
			return err
		}
		*tgt = s.Value().(ZBool)
		return nil
	case *ZInt:
		s, err := z.As(ctx, ZtInt)
		if err != nil {
			return err
		}
		*tgt = s.Value().(ZInt)
		return nil
	case *ZFloat:
		s, err := z.As(ctx, ZtFloat)
		if err != nil {
			return err
		}
		*tgt = s.Value().(ZFloat)
		return nil
	case *string:
		s, err := z.As(ctx, ZtString)
		if err != nil {
			return err
		}
		*tgt = string(s.Value().(ZString))
		return nil
	case *ZString:
		s, err := z.As(ctx, ZtString)
		if err != nil {
			return err
		}
		*tgt = s.Value().(ZString)
		return nil
	case **ZVal:
		// as is
		*tgt = z
		return nil
	default:
		return fmt.Errorf("unsupported target type %T", out)
	}
}

func Expand(ctx Context, args []*ZVal, out ...interface{}) (int, error) {
	for i, v := range out {
		rv := reflect.ValueOf(v)
		if rv.Kind() != reflect.Ptr {
			panic("expand requires arguments to be pointers")
		}
		if rv.Type().Elem().Kind() == reflect.Ptr && rv.Type().Elem() != reflect.TypeOf(&ZVal{}) {
			// pointer of pointer → optional argument
			if len(args) <= i {
				// end of argments
				return i, nil
			}
			newv := reflect.New(rv.Type().Elem().Elem())
			rv.Elem().Set(newv)
			v = newv.Interface()
		}

		err := args[i].Store(ctx, v)
		if err != nil {
			return i, err
		}
	}
	return len(out), nil
}