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
	virtual void Serialize(Stream& s) {s % fs;}
	
private:
	FileSel fs;
	Point llCorner;
	Vector<short> raster;

	static const int strmSize = 6000;
	static const int previewSize = 600;
	static const int elevFactor = 20;

	void Convert(void);
	void Open(void);	
	void ComputeImg(void);	
	void StoreStrm(void);
	
};

#endif

