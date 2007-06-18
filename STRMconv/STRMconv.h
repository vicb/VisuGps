#ifndef _STRMconv_STRMconv_h
#define _STRMconv_STRMconv_h

#include <CtrlLib/CtrlLib.h>

using namespace Upp;

#define LAYOUTFILE <STRMconv/STRMconv.lay>
#include <CtrlCore/lay.h>



class STRMconv : public WithSTRMconvLayout<TopWindow> {
public:
	typedef STRMconv CLASSNAME;
	STRMconv();
	
private:
	FileSelector fs;

	void Convert(void);
	
	void Open(void);
	
	void ComputeImg(void);
	
	void StoreStrm(void);

	Point llCorner;
	
	Vector<short> raster;
};

#endif

