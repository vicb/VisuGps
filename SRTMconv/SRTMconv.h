#ifndef _SRTMconv_SRTMconv_h
#define _SRTMconv_SRTMconv_h

#include <CtrlLib/CtrlLib.h>

using namespace Upp;

#define LAYOUTFILE <SRTMconv/SRTMconv.lay>
#include <CtrlCore/lay.h>

class SRTMconv : public WithSRTMconvLayout<TopWindow> {
public:
	typedef SRTMconv CLASSNAME;
	SRTMconv();
	virtual void Serialize(Stream& s) {s % fs;}
	
private:
	FileSel fs;
	Point llCorner;
	Vector<short> raster;

	static const int srtmSize = 6000;
	static const int previewSize = 600;
	static const int elevFactor = 20;

	void Convert(void);
	void Open(void);	
	void ComputeImg(void);	
	void StoreSrtm(void);

};

#endif
